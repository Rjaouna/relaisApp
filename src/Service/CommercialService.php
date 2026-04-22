<?php

namespace App\Service;

use App\Repository\CommercialRepository;

class CommercialService
{
    public function __construct(
        private readonly CommercialRepository $commercialRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->commercialRepository->findActiveOrdered();
    }
}
