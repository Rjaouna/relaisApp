<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Commercial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return Client[]
     */
    public function findForListing(): array
    {
        return $this->createQueryBuilder('client')
            ->orderBy('client.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(client.id)')
            ->andWhere('client.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Client[]
     */
    public function findAssignable(): array
    {
        return $this->createQueryBuilder('client')
            ->orderBy('client.potentialScore', 'DESC')
            ->addOrderBy('client.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Client[]
     */
    public function findAvailableForVisitPlanning(): array
    {
        return $this->createQueryBuilder('client')
            ->leftJoin(
                'client.visits',
                'visit',
                'WITH',
                'visit.archivedAt IS NULL AND visit.status IN (:openStatuses)'
            )
            ->groupBy('client.id')
            ->having('COUNT(visit.id) = 0')
            ->setParameter('openStatuses', ['prevue', 'en_attente'])
            ->orderBy('client.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAssigned(): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(client.id)')
            ->andWhere('client.assignedCommercial IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForCommercial(Commercial $commercial): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(client.id)')
            ->andWhere('client.assignedCommercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Client[]
     */
    public function findForCommercial(Commercial $commercial): array
    {
        return $this->createQueryBuilder('client')
            ->andWhere('client.assignedCommercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->orderBy('client.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countNewActiveForCommercialInPeriod(Commercial $commercial, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(client.id)')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('client.status = :status')
            ->andWhere('client.createdAt >= :start')
            ->andWhere('client.createdAt < :end')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Client::STATUS_ACTIVE)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countConvertedForCommercialInPeriod(Commercial $commercial, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(DISTINCT client.id)')
            ->innerJoin('client.visits', 'visit')
            ->andWhere('client.assignedCommercial = :commercial')
            ->andWhere('visit.result = :result')
            ->andWhere('visit.adminReviewStatus = :reviewStatus')
            ->andWhere('((visit.adminReviewedAt IS NOT NULL AND visit.adminReviewedAt >= :start AND visit.adminReviewedAt < :end) OR (visit.adminReviewedAt IS NULL AND visit.scheduledAt >= :start AND visit.scheduledAt < :end))')
            ->setParameter('commercial', $commercial)
            ->setParameter('result', 'commande_confirmee')
            ->setParameter('reviewStatus', 'validee')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
