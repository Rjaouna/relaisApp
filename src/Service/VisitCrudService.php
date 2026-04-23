<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class VisitCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VisitRepository $visitRepository,
        private readonly ClientRepository $clientRepository,
        private readonly ObjectivePerformanceService $objectivePerformanceService,
        private readonly AppointmentCrudService $appointmentCrudService,
    ) {
    }

    public function getListing(): array
    {
        return $this->visitRepository->findForListing();
    }

    public function getArchivedListing(): array
    {
        return $this->visitRepository->findForListing(true);
    }

    /**
     * @return Client[]
     */
    public function getClientsAvailableForPlanning(): array
    {
        return $this->clientRepository->findAvailableForVisitPlanning();
    }

    /**
     * @param int[] $clientIds
     *
     * @return array{created:int, skipped:int}
     */
    public function createBatch(array $clientIds): array
    {
        $created = 0;
        $skipped = 0;
        $availableClients = [];

        foreach ($this->getClientsAvailableForPlanning() as $client) {
            if ($client->getId() !== null) {
                $availableClients[$client->getId()] = $client;
            }
        }

        foreach (array_unique(array_map('intval', $clientIds)) as $clientId) {
            $client = $availableClients[$clientId] ?? null;
            if (!$client instanceof Client) {
                ++$skipped;
                continue;
            }

            $visit = new Visit();
            $visit->setClient($client);
            $visit->setScheduledAt($visit->getCreatedAt());
            $visit->touch();

            $this->entityManager->persist($visit);
            ++$created;
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    public function save(Visit $visit): void
    {
        if (!$visit->getScheduledAt() instanceof \DateTimeImmutable) {
            $visit->setScheduledAt($visit->getCreatedAt());
        }

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
            $visit
                ->setAdminReviewStatus(Visit::REVIEW_PENDING)
                ->setAdminReviewComment(null)
                ->setAdminReviewedAt(null);
        }

        if ($visit->getResult() === Visit::RESULT_APPOINTMENT_BOOKED) {
            $this->appointmentCrudService->validateNoConflictForVisit($visit);
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
        $this->appointmentCrudService->syncFromVisit($visit);
        $this->entityManager->flush();

        $this->objectivePerformanceService->syncAllObjectivesForCommercial(
            $visit->getClient()?->getAssignedCommercial()
        );
    }

    public function review(Visit $visit, string $decision, ?string $comment): void
    {
        if (!in_array($decision, [Visit::REVIEW_VALIDATED, Visit::REVIEW_REJECTED], true)) {
            throw new \LogicException('Decision de controle invalide.');
        }

        if ($visit->getResult() === null || $visit->getStatus() !== Visit::STATUS_COMPLETED) {
            throw new \LogicException('Seules les visites renseignees peuvent etre controlees.');
        }

        $visit
            ->setAdminReviewStatus($decision)
            ->setAdminReviewComment($comment ?: null)
            ->setAdminReviewedAt(new \DateTimeImmutable());

        $visit->touch();
        $this->entityManager->persist($visit);
        $this->entityManager->flush();

        $this->objectivePerformanceService->syncAllObjectivesForCommercial(
            $visit->getClient()?->getAssignedCommercial()
        );
    }

    public function delete(Visit $visit): void
    {
        $this->entityManager->remove($visit);
        $this->entityManager->flush();
    }

    /**
     * @param Visit[] $visits
     */
    public function archiveVisits(array $visits): void
    {
        $archivedAt = new \DateTimeImmutable();

        foreach ($visits as $visit) {
            $visit->setArchivedAt($archivedAt);
            $visit->touch();
            $this->entityManager->persist($visit);
        }
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
