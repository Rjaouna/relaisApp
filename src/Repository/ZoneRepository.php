<?php

namespace App\Repository;

use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    /**
     * @return Zone[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('zone')
            ->leftJoin('zone.city', 'city')
            ->addSelect('city')
            ->andWhere('zone.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('city.name', 'ASC')
            ->addOrderBy('zone.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
