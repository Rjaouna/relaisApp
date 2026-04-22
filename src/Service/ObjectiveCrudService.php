<?php

namespace App\Service;

use App\Entity\Objective;
use App\Repository\ObjectiveRepository;
use Doctrine\ORM\EntityManagerInterface;

class ObjectiveCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectiveRepository $objectiveRepository,
        private readonly ObjectivePerformanceService $objectivePerformanceService,
    ) {
    }

    public function getListing(): array
    {
        return $this->objectivePerformanceService->hydrateObjectives(
            $this->objectiveRepository->findBy([], ['periodLabel' => 'DESC'])
        );
    }

    public function hydrate(Objective $objective): Objective
    {
        return $this->objectivePerformanceService->hydrateObjective($objective);
    }

    public function save(Objective $objective): void
    {
        $this->objectivePerformanceService->hydrateObjective($objective);
        $this->entityManager->persist($objective);
        $this->entityManager->flush();
    }

    public function delete(Objective $objective): void
    {
        $this->entityManager->remove($objective);
        $this->entityManager->flush();
    }
}
