<?php

namespace App\Service;

use App\Entity\Objective;
use App\Entity\Commercial;
use App\Repository\ObjectiveRepository;
use Doctrine\ORM\EntityManagerInterface;

class ObjectiveCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectiveRepository $objectiveRepository,
        private readonly ObjectivePerformanceService $objectivePerformanceService,
        private readonly ObjectivePlanningService $objectivePlanningService,
    ) {
    }

    public function getListing(): array
    {
        return $this->objectivePerformanceService->hydrateObjectives(
            $this->objectiveRepository->findValidForListing()
        );
    }

    public function getListingForCommercial(Commercial $commercial): array
    {
        return $this->objectivePerformanceService->hydrateObjectives(
            $this->objectiveRepository->findForCommercial($commercial)
        );
    }

    public function hydrate(Objective $objective): Objective
    {
        return $this->objectivePerformanceService->hydrateObjective($objective);
    }

    public function belongsToCommercial(Objective $objective, Commercial $commercial): bool
    {
        return $objective->getCommercial()?->getId() === $commercial->getId();
    }

    /**
     * @return array<string, mixed>
     */
    public function getInsights(Objective $objective): array
    {
        return $this->objectivePerformanceService->buildObjectiveInsights($objective);
    }

    public function save(Objective $objective): void
    {
        $existing = $this->objectiveRepository->findOneForCommercialAndPeriod(
            $objective->getCommercial(),
            (string) $objective->getPeriodLabel(),
            $objective->getId()
        );

        if ($existing instanceof Objective) {
            throw new \DomainException('Un objectif existe deja pour ce commercial sur cette periode.');
        }

        $this->objectivePerformanceService->hydrateObjective($objective);
        $this->entityManager->persist($objective);
        $this->entityManager->flush();
    }

    public function delete(Objective $objective): void
    {
        $this->entityManager->remove($objective);
        $this->entityManager->flush();
    }

    public function getPlanningContext(?Commercial $commercial, ?string $periodLabel, ?Objective $objective = null): array
    {
        return $this->objectivePlanningService->buildContext($commercial, $periodLabel, $objective);
    }

    public function getSuggestedPeriodLabel(): string
    {
        return $this->objectivePlanningService->getSuggestedPeriodLabel();
    }
}
