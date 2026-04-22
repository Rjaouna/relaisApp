<?php

namespace App\Service;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;

class SupplierCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierRepository $supplierRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->supplierRepository->findBy([], ['name' => 'ASC']);
    }

    public function save(Supplier $supplier): void
    {
        $this->entityManager->persist($supplier);
        $this->entityManager->flush();
    }

    public function delete(Supplier $supplier): void
    {
        $this->entityManager->remove($supplier);
        $this->entityManager->flush();
    }
}
