<?php

namespace App\Service;

use App\Repository\MarketRepository;

class MarketService
{
    public function __construct(
        private readonly MarketRepository $marketRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->marketRepository->findBy([], ['globalScore' => 'DESC']);
    }
}
