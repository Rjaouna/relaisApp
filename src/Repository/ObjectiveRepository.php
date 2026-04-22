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
}
