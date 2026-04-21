<?php

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    /**
     * @return Offer[]
     */
    public function findLatest(): array
    {
        return $this->createQueryBuilder('offer')
            ->leftJoin('offer.client', 'client')
            ->addSelect('client')
            ->orderBy('offer.issuedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function sumCurrentMonth(\DateTimeImmutable $startOfMonth): float
    {
        return (float) $this->createQueryBuilder('offer')
            ->select('COALESCE(SUM(offer.amount), 0)')
            ->andWhere('offer.issuedAt >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
