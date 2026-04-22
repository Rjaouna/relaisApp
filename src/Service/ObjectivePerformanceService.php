<?php

namespace App\Service;

use App\Entity\Commercial;
use App\Entity\Objective;
use App\Repository\ClientRepository;
use App\Repository\ObjectiveRepository;
use App\Repository\OfferRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class ObjectivePerformanceService
{
    private const MONTHS = [
        'janvier' => 1,
        'fevrier' => 2,
        'mars' => 3,
        'avril' => 4,
        'mai' => 5,
        'juin' => 6,
        'juillet' => 7,
        'aout' => 8,
        'septembre' => 9,
        'octobre' => 10,
        'novembre' => 11,
        'decembre' => 12,
    ];

    public function __construct(
        private readonly VisitRepository $visitRepository,
        private readonly ClientRepository $clientRepository,
        private readonly OfferRepository $offerRepository,
        private readonly ObjectiveRepository $objectiveRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param Objective[] $objectives
     *
     * @return Objective[]
     */
    public function hydrateObjectives(array $objectives): array
    {
        foreach ($objectives as $objective) {
            $this->hydrateObjective($objective);
        }

        return $objectives;
    }

    public function hydrateObjective(Objective $objective): Objective
    {
        $period = $this->resolvePeriodRange($objective->getPeriodLabel());
        if ($period === null || $objective->getCommercial() === null) {
            return $objective;
        }

        [$start, $end] = $period;
        $commercial = $objective->getCommercial();

        $objective
            ->setVisitsActual($this->visitRepository->countCompletedForCommercialInPeriod($commercial, $start, $end))
            ->setNewClientsActual($this->clientRepository->countConvertedForCommercialInPeriod($commercial, $start, $end))
            ->setSalesActual((int) round($this->offerRepository->sumAcceptedForCommercialInPeriod($commercial, $start, $end)));

        return $objective;
    }

    public function syncObjectivesForCommercialAtDate(?Commercial $commercial, ?\DateTimeImmutable $referenceDate = null): void
    {
        if (!$commercial instanceof Commercial) {
            return;
        }

        $referenceDate ??= new \DateTimeImmutable();

        foreach ($this->objectiveRepository->findForCommercial($commercial) as $objective) {
            $period = $this->resolvePeriodRange($objective->getPeriodLabel());
            if ($period === null) {
                continue;
            }

            [$start, $end] = $period;
            if ($referenceDate < $start || $referenceDate >= $end) {
                continue;
            }

            $this->hydrateObjective($objective);
            $this->entityManager->persist($objective);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}|null
     */
    private function resolvePeriodRange(?string $periodLabel): ?array
    {
        if ($periodLabel === null) {
            return null;
        }

        $normalized = $this->normalizePeriodLabel($periodLabel);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if (!preg_match('/^([a-z]+)\s+(\d{4})$/', $normalized, $matches)) {
            return null;
        }

        $month = self::MONTHS[$matches[1]] ?? null;
        $year = (int) $matches[2];

        if ($month === null || $year < 2000) {
            return null;
        }

        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));

        return [$start, $start->modify('+1 month')];
    }

    private function normalizePeriodLabel(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $normalized === false ? $value : $normalized;

        return mb_strtolower(trim($normalized), 'UTF-8');
    }
}
