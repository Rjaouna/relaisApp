<?php

namespace App\Service;

use App\Entity\SupplierConsultation;
use App\Repository\SupplierConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SupplierConsultationCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierConsultationRepository $repository,
    ) {
    }

    public function getListing(): array
    {
        return $this->repository->findBy([], ['createdAt' => 'DESC']);
    }

    public function save(SupplierConsultation $consultation): void
    {
        $consultation->touch();
        $this->entityManager->persist($consultation);
        $this->entityManager->flush();
    }

    public function delete(SupplierConsultation $consultation): void
    {
        $this->entityManager->remove($consultation);
        $this->entityManager->flush();
    }
}
