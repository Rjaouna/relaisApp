<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Visit;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class VisitCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VisitRepository $visitRepository,
        private readonly ObjectivePerformanceService $objectivePerformanceService,
    ) {
    }

    public function getListing(): array
    {
        return $this->visitRepository->findBy([], ['scheduledAt' => 'DESC']);
    }

    public function save(Visit $visit): void
    {
        if (
            $visit->getClient() !== null
            && $visit->getStatus() === Visit::STATUS_PLANNED
            && $this->visitRepository->hasAnotherPlannedVisitForClient($visit->getClient(), $visit->getId())
        ) {
            throw new \LogicException('Impossible de mettre cette visite en prevue : ce client a deja une autre visite prevue.');
        }

        // Once a visit outcome is captured, the visit is considered completed.
        if ($visit->getResult() !== null) {
            $visit->setStatus(Visit::STATUS_COMPLETED);
        }

        $this->synchronizeClientStatusFromVisitResult($visit);

        if ($visit->getStatus() === Visit::STATUS_COMPLETED) {
            $visit->getClient()?->setLastVisitAt($visit->getScheduledAt());
            if ($visit->getClient() !== null) {
                $this->entityManager->persist($visit->getClient());
            }
        }

        $visit->touch();
        $this->entityManager->persist($visit);
        $this->entityManager->flush();

        $this->objectivePerformanceService->syncObjectivesForCommercialAtDate(
            $visit->getClient()?->getAssignedCommercial(),
            $visit->getScheduledAt()
        );
    }

    public function delete(Visit $visit): void
    {
        $this->entityManager->remove($visit);
        $this->entityManager->flush();
    }

    public function getLatestForClient(Client $client, ?int $excludedVisitId = null): ?Visit
    {
        return $this->visitRepository->findLatestForClient($client, $excludedVisitId);
    }

    public function hasAnotherPlannedVisitForClient(Client $client, ?int $excludedVisitId = null): bool
    {
        return $this->visitRepository->hasAnotherPlannedVisitForClient($client, $excludedVisitId);
    }

    private function synchronizeClientStatusFromVisitResult(Visit $visit): void
    {
        $client = $visit->getClient();
        $result = $visit->getResult();

        if ($client === null || $result === null) {
            return;
        }

        $currentStatus = $client->getStatus();
        $nextStatus = match ($result) {
            Visit::RESULT_NOT_INTERESTED => Client::STATUS_REFUSED,
            Visit::RESULT_ORDER_CONFIRMED => Client::STATUS_ACTIVE,
            Visit::RESULT_APPOINTMENT_BOOKED,
            Visit::RESULT_QUOTE_SENT,
            Visit::RESULT_FOLLOW_UP => Client::STATUS_IN_PROGRESS,
            default => null,
        };

        if ($nextStatus === null) {
            return;
        }

        $shouldSynchronize = match ($currentStatus) {
            Client::STATUS_POTENTIAL => true,
            Client::STATUS_IN_PROGRESS => $nextStatus !== Client::STATUS_IN_PROGRESS,
            Client::STATUS_REFUSED => in_array($nextStatus, [
                Client::STATUS_IN_PROGRESS,
                Client::STATUS_ACTIVE,
            ], true),
            default => false,
        };

        if (!$shouldSynchronize) {
            return;
        }

        $client->setStatus($nextStatus);
        $client->touch();
        $this->entityManager->persist($client);
    }
}
