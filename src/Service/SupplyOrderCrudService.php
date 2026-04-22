<?php

namespace App\Service;

use App\Entity\SupplyOrder;
use App\Repository\SupplyOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class SupplyOrderCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplyOrderRepository $supplyOrderRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->supplyOrderRepository->findBy([], ['orderedAt' => 'DESC']);
    }

    public function save(SupplyOrder $supplyOrder): void
    {
        $this->entityManager->persist($supplyOrder);
        $this->entityManager->flush();
    }

    public function delete(SupplyOrder $supplyOrder): void
    {
        $this->entityManager->remove($supplyOrder);
        $this->entityManager->flush();
    }
}
