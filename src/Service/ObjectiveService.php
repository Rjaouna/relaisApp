<?php

namespace App\Service;

use App\Repository\ObjectiveRepository;

class ObjectiveService
{
    public function __construct(
        private readonly ObjectiveRepository $objectiveRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->objectiveRepository->findBy([], ['periodLabel' => 'DESC']);
    }
}
