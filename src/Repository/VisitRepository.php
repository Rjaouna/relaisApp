<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Tour;
use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    /**
     * @return Visit[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('visit')
            ->leftJoin('visit.client', 'client')
            ->addSelect('client')
            ->orderBy('visit.scheduledAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function countCompletedThisMonth(\DateTimeImmutable $startOfMonth): int
    {
        return (int) $this->createQueryBuilder('visit')
            ->select('COUNT(visit.id)')
            ->andWhere('visit.status = :status')
            ->andWhere('visit.scheduledAt >= :start')
            ->setParameter('status', Visit::STATUS_COMPLETED)
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Visit[]
     */
    public function findForTour(Tour $tour): array
    {
        $start = $tour->getScheduledFor()?->setTime(0, 0, 0) ?? new \DateTimeImmutable('today midnight');
        $end = $start->modify('+1 day');

        return $this->createQueryBuilder('visit')
            ->leftJoin('visit.client', 'client')
            ->addSelect('client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('client.city = :city')
            ->andWhere('visit.scheduledAt >= :start')
            ->andWhere('visit.scheduledAt < :end')
            ->setParameter('commercial', $tour->getCommercial())
            ->setParameter('city', $tour->getCity())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('visit.priority', 'DESC')
            ->addOrderBy('visit.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestForClient(Client $client, ?int $excludedVisitId = null): ?Visit
    {
        $queryBuilder = $this->createQueryBuilder('visit')
            ->andWhere('visit.client = :client')
            ->setParameter('client', $client)
            ->orderBy('visit.scheduledAt', 'DESC')
            ->addOrderBy('visit.id', 'DESC')
            ->setMaxResults(1);

        if ($excludedVisitId !== null) {
            $queryBuilder
                ->andWhere('visit.id != :excludedVisitId')
                ->setParameter('excludedVisitId', $excludedVisitId);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return int[]
     */
    public function findClientIdsWithPlannedVisits(?int $excludedVisitId = null): array
    {
        $queryBuilder = $this->createQueryBuilder('visit')
            ->select('IDENTITY(visit.client) AS clientId')
            ->andWhere('visit.status = :status')
            ->setParameter('status', Visit::STATUS_PLANNED)
            ->groupBy('visit.client');

        if ($excludedVisitId !== null) {
            $queryBuilder
                ->andWhere('visit.id != :excludedVisitId')
                ->setParameter('excludedVisitId', $excludedVisitId);
        }

        return array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['clientId'] ?? 0),
            $queryBuilder->getQuery()->getArrayResult()
        )));
    }

    public function hasAnotherPlannedVisitForClient(Client $client, ?int $excludedVisitId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('visit')
            ->select('COUNT(visit.id)')
            ->andWhere('visit.client = :client')
            ->andWhere('visit.status = :status')
            ->setParameter('client', $client)
            ->setParameter('status', Visit::STATUS_PLANNED);

        if ($excludedVisitId !== null) {
            $queryBuilder
                ->andWhere('visit.id != :excludedVisitId')
                ->setParameter('excludedVisitId', $excludedVisitId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param string[] $clientStatuses
     *
     * @return Visit[]
     */
    public function findPlannedForTourGeneration(array $clientStatuses): array
    {
        $queryBuilder = $this->createQueryBuilder('visit')
            ->leftJoin('visit.client', 'client')
            ->addSelect('client')
            ->andWhere('visit.status = :status')
            ->setParameter('status', Visit::STATUS_PLANNED)
            ->orderBy('visit.scheduledAt', 'ASC');

        if ($clientStatuses !== []) {
            $queryBuilder
                ->andWhere('client.status IN (:clientStatuses)')
                ->setParameter('clientStatuses', $clientStatuses);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function countPlannedForCommercial(Commercial $commercial): int
    {
        return (int) $this->createQueryBuilder('visit')
            ->select('COUNT(visit.id)')
            ->leftJoin('visit.client', 'client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('visit.status = :status')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Visit::STATUS_PLANNED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedForCommercial(Commercial $commercial): int
    {
        return (int) $this->createQueryBuilder('visit')
            ->select('COUNT(visit.id)')
            ->leftJoin('visit.client', 'client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('visit.status = :status')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Visit::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findNextPlannedForCommercial(Commercial $commercial): ?Visit
    {
        return $this->createQueryBuilder('visit')
            ->leftJoin('visit.client', 'client')
            ->addSelect('client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('visit.status = :status')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Visit::STATUS_PLANNED)
            ->orderBy('visit.scheduledAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countCompletedForCommercialInPeriod(Commercial $commercial, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('visit')
            ->select('COUNT(visit.id)')
            ->leftJoin('visit.client', 'client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('visit.status = :status')
            ->andWhere('visit.scheduledAt >= :start')
            ->andWhere('visit.scheduledAt < :end')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Visit::STATUS_COMPLETED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
