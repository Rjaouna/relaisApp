<?php

namespace App\Service;

use App\Entity\CustomerSatisfaction;
use App\Repository\CustomerSatisfactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomerSatisfactionCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerSatisfactionRepository $repository,
    ) {
    }

    public function getListing(): array
    {
        return $this->repository->findBy([], ['createdAt' => 'DESC']);
    }

    public function save(CustomerSatisfaction $satisfaction): void
    {
        $satisfaction->touch();
        $this->entityManager->persist($satisfaction);
        $this->entityManager->flush();
    }

    public function delete(CustomerSatisfaction $satisfaction): void
    {
        $this->entityManager->remove($satisfaction);
        $this->entityManager->flush();
    }
}
