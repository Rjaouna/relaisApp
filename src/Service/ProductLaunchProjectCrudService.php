<?php

namespace App\Service;

use App\Entity\ProductLaunchProject;
use App\Repository\ProductLaunchProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductLaunchProjectCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductLaunchProjectRepository $repository,
    ) {
    }

    public function getListing(): array
    {
        return $this->repository->findBy([], ['createdAt' => 'DESC']);
    }

    public function save(ProductLaunchProject $project): void
    {
        $project->touch();
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function delete(ProductLaunchProject $project): void
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }
}
