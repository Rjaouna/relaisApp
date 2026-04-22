<?php

namespace App\Repository;

use App\Entity\ReferenceOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReferenceOption>
 */
class ReferenceOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferenceOption::class);
    }

    /**
     * @return ReferenceOption[]
     */
    public function findActiveByCategory(string $category): array
    {
        return $this->createQueryBuilder('reference_option')
            ->andWhere('reference_option.category = :category')
            ->andWhere('reference_option.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('reference_option.sortOrder', 'ASC')
            ->addOrderBy('reference_option.label', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
