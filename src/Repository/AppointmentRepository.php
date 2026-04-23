<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Commercial;
use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @return Appointment[]
     */
    public function findForListing(): array
    {
        return $this->createQueryBuilder('appointment')
            ->leftJoin('appointment.client', 'client')
            ->addSelect('client')
            ->leftJoin('appointment.commercial', 'commercial')
            ->addSelect('commercial')
            ->leftJoin('appointment.visit', 'visit')
            ->addSelect('visit')
            ->orderBy('appointment.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Appointment[]
     */
    public function findForCommercial(Commercial $commercial): array
    {
        return $this->createQueryBuilder('appointment')
            ->leftJoin('appointment.client', 'client')
            ->addSelect('client')
            ->leftJoin('appointment.commercial', 'commercial')
            ->addSelect('commercial')
            ->leftJoin('appointment.visit', 'visit')
            ->addSelect('visit')
            ->andWhere('appointment.commercial = :commercial')
            ->setParameter('commercial', $commercial)
            ->orderBy('appointment.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByVisitId(int $visitId): ?Appointment
    {
        return $this->createQueryBuilder('appointment')
            ->andWhere('IDENTITY(appointment.visit) = :visitId')
            ->setParameter('visitId', $visitId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasConflictForCommercial(Commercial $commercial, \DateTimeImmutable $scheduledAt, ?Visit $excludedVisit = null): bool
    {
        $start = $scheduledAt->modify('-59 minutes');
        $end = $scheduledAt->modify('+59 minutes');

        $queryBuilder = $this->createQueryBuilder('appointment')
            ->select('COUNT(appointment.id)')
            ->andWhere('appointment.commercial = :commercial')
            ->andWhere('appointment.status = :status')
            ->andWhere('appointment.scheduledAt >= :start')
            ->andWhere('appointment.scheduledAt <= :end')
            ->setParameter('commercial', $commercial)
            ->setParameter('status', Appointment::STATUS_PLANNED)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludedVisit?->getId() !== null) {
            $queryBuilder
                ->andWhere('IDENTITY(appointment.visit) != :excludedVisitId')
                ->setParameter('excludedVisitId', $excludedVisit->getId());
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
