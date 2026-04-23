<?php

namespace App\Service;

use App\Entity\FieldFeedback;
use App\Repository\FieldFeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;

class FieldFeedbackCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FieldFeedbackRepository $repository,
    ) {
    }

    public function getListing(): array
    {
        return $this->repository->findBy([], ['createdAt' => 'DESC']);
    }

    public function save(FieldFeedback $feedback): void
    {
        $feedback->touch();
        $this->entityManager->persist($feedback);
        $this->entityManager->flush();
    }

    public function delete(FieldFeedback $feedback): void
    {
        $this->entityManager->remove($feedback);
        $this->entityManager->flush();
    }
}
