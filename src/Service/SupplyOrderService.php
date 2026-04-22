<?php

namespace App\Service;

use App\Repository\SupplyOrderRepository;

class SupplyOrderService
{
    public function __construct(
        private readonly SupplyOrderRepository $supplyOrderRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->supplyOrderRepository->findBy([], ['orderedAt' => 'DESC']);
    }
}
