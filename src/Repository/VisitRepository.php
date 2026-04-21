<?php

namespace App\Repository;

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
}
