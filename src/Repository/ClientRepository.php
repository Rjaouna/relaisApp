<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\Zone;
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

    /**
     * @return Client[]
     */
    public function findForZone(Zone $zone): array
    {
        return $this->createQueryBuilder('client')
            ->leftJoin('client.zone', 'zone')
            ->addSelect('zone')
            ->andWhere('client.zone = :zone')
            ->setParameter('zone', $zone)
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

    /**
     * @param array<string, mixed> $filters
     *
     * @return Client[]
     */
    public function findForMap(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('client')
            ->leftJoin('client.assignedCommercial', 'commercial')
            ->addSelect('commercial')
            ->leftJoin('client.zone', 'zone')
            ->addSelect('zone')
            ->leftJoin('zone.city', 'zoneCity')
            ->addSelect('zoneCity')
            ->orderBy('client.name', 'ASC');

        if (!empty($filters['commercial'])) {
            $queryBuilder
                ->andWhere('commercial.id = :commercialId')
                ->setParameter('commercialId', (int) $filters['commercial']);
        }

        if (!empty($filters['zone'])) {
            $queryBuilder
                ->andWhere('zone.id = :zoneId')
                ->setParameter('zoneId', (int) $filters['zone']);
        }

        if (!empty($filters['city'])) {
            $queryBuilder
                ->andWhere('client.city = :city')
                ->setParameter('city', (string) $filters['city']);
        }

        if (!empty($filters['type'])) {
            $queryBuilder
                ->andWhere('client.type = :type')
                ->setParameter('type', (string) $filters['type']);
        }

        if (!empty($filters['status'])) {
            $queryBuilder
                ->andWhere('client.status = :status')
                ->setParameter('status', (string) $filters['status']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $normalized = mb_strtolower($search);
            $searchExpression = $queryBuilder->expr()->orX(
                'LOWER(client.name) LIKE :search',
                'LOWER(client.city) LIKE :search',
                'LOWER(COALESCE(client.address, \'\')) LIKE :search'
            );

            $searchDigits = preg_replace('/\D+/', '', $search);
            if ($searchDigits !== '' && ctype_digit($searchDigits)) {
                $searchExpression->add('client.id = :clientIdSearch');
                $queryBuilder->setParameter('clientIdSearch', (int) $searchDigits);
            }

            $queryBuilder
                ->andWhere($searchExpression)
                ->setParameter('search', '%' . $normalized . '%');
        }

        if (!empty($filters['has_active_visit'])) {
            $queryBuilder
                ->andWhere('EXISTS (
                    SELECT activeVisit.id
                    FROM App\Entity\Visit activeVisit
                    WHERE activeVisit.client = client
                    AND activeVisit.archivedAt IS NULL
                    AND activeVisit.status IN (:activeVisitStatuses)
                )')
                ->setParameter('activeVisitStatuses', ['prevue', 'en_attente']);
        }

        if (!empty($filters['has_planned_visit'])) {
            $queryBuilder
                ->andWhere('EXISTS (
                    SELECT plannedVisit.id
                    FROM App\Entity\Visit plannedVisit
                    WHERE plannedVisit.client = client
                    AND plannedVisit.archivedAt IS NULL
                    AND plannedVisit.status = :plannedVisitStatus
                )')
                ->setParameter('plannedVisitStatus', 'prevue');
        }

        if (!empty($filters['assigned_to_tour'])) {
            $queryBuilder
                ->andWhere('EXISTS (
                    SELECT tourVisit.id
                    FROM App\Entity\Visit tourVisit
                    JOIN tourVisit.tour relatedTour
                    WHERE tourVisit.client = client
                    AND tourVisit.archivedAt IS NULL
                    AND relatedTour.archivedAt IS NULL
                )');
        }

        if (!empty($filters['without_tour'])) {
            $queryBuilder
                ->andWhere('NOT EXISTS (
                    SELECT freeVisit.id
                    FROM App\Entity\Visit freeVisit
                    JOIN freeVisit.tour usedTour
                    WHERE freeVisit.client = client
                    AND freeVisit.archivedAt IS NULL
                    AND usedTour.archivedAt IS NULL
                )');
        }

        if (!empty($filters['without_visit'])) {
            $queryBuilder
                ->andWhere('NOT EXISTS (
                    SELECT noVisit.id
                    FROM App\Entity\Visit noVisit
                    WHERE noVisit.client = client
                    AND noVisit.archivedAt IS NULL
                )');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function getCityOptionsForMap(): array
    {
        $rows = $this->createQueryBuilder('client')
            ->select('DISTINCT client.city AS name')
            ->andWhere('client.city IS NOT NULL')
            ->andWhere('client.city != :empty')
            ->setParameter('empty', '')
            ->orderBy('client.city', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $options = [];
        $index = 1;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $options[] = [
                'id' => $index++,
                'name' => $name,
            ];
        }

        return $options;
    }
}
