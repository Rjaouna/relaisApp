<?php

namespace App\Repository;

use App\Entity\Commercial;
use App\Entity\Objective;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objective>
 */
class ObjectiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objective::class);
    }

    /**
     * @return Objective[]
     */
    public function findForCommercial(Commercial $commercial): array
    {
        return $this->createQueryBuilder('objective')
            ->andWhere('objective.commercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->orderBy('objective.periodLabel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForCommercialAndPeriod(Commercial $commercial, string $periodLabel, ?int $excludedId = null): ?Objective
    {
        $queryBuilder = $this->createQueryBuilder('objective')
            ->andWhere('objective.commercial = :commercial')
            ->andWhere('LOWER(objective.periodLabel) = :periodLabel')
            ->setParameter('commercial', $commercial)
            ->setParameter('periodLabel', mb_strtolower(trim($periodLabel)));

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('objective.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForCommercial(Commercial $commercial, ?int $excludedId = null): ?Objective
    {
        $queryBuilder = $this->createQueryBuilder('objective')
            ->andWhere('objective.commercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->orderBy('objective.id', 'DESC');

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('objective.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
