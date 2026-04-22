<?php

namespace App\Repository;

use App\Entity\Commercial;
use App\Entity\Tour;
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
}
