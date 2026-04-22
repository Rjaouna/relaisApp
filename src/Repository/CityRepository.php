<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    /**
     * @return City[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('city')
            ->andWhere('city.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('city.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
