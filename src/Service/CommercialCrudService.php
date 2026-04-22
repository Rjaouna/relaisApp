<?php

namespace App\Service;

use App\Entity\Commercial;
use App\Repository\CommercialRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommercialCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommercialRepository $commercialRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->commercialRepository->findBy([], ['fullName' => 'ASC']);
    }

    public function save(Commercial $commercial): void
    {
        $commercial->touch();
        $this->entityManager->persist($commercial);
        $this->entityManager->flush();
    }

    public function delete(Commercial $commercial): void
    {
        $this->entityManager->remove($commercial);
        $this->entityManager->flush();
    }
}
