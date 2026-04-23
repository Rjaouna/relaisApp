<?php

namespace App\Repository;

use App\Entity\Commercial;
use App\Entity\Tour;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tour>
 */
class TourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tour::class);
    }

    /**
     * @return Tour[]
     */
    public function findUpcomingTours(): array
    {
        return $this->createQueryBuilder('tour')
            ->leftJoin('tour.commercial', 'commercial')
            ->addSelect('commercial')
            ->orderBy('tour.scheduledFor', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tour[]
     */
    public function findForCommercial(Commercial $commercial): array
    {
        return $this->createQueryBuilder('tour')
            ->leftJoin('tour.commercial', 'commercial')
            ->addSelect('commercial')
            ->andWhere('tour.commercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->orderBy('tour.scheduledFor', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('tour')
            ->select('COUNT(tour.id)')
            ->andWhere('tour.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findReusableForGeneration(Commercial $commercial, Zone $zone): ?Tour
    {
        return $this->createQueryBuilder('tour')
            ->andWhere('tour.commercial = :commercial')
            ->andWhere('tour.zone = :zone')
            ->andWhere('tour.archivedAt IS NULL')
            ->andWhere('tour.closureRequestedAt IS NULL')
            ->andWhere('tour.status = :status')
            ->setParameter('commercial', $commercial)
            ->setParameter('zone', $zone)
            ->setParameter('status', Tour::STATUS_PROGRAMMED)
            ->orderBy('tour.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Tour[]
     */
    public function findPendingClosureRequests(): array
    {
        return $this->createQueryBuilder('tour')
            ->leftJoin('tour.commercial', 'commercial')
            ->addSelect('commercial')
            ->andWhere('tour.closureRequestedAt IS NOT NULL')
            ->andWhere('tour.status != :completed')
            ->andWhere('tour.archivedAt IS NULL')
            ->setParameter('completed', Tour::STATUS_COMPLETED)
            ->orderBy('tour.closureRequestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
