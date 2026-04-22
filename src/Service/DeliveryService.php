<?php

namespace App\Service;

use App\Repository\DeliveryRepository;

class DeliveryService
{
    public function __construct(
        private readonly DeliveryRepository $deliveryRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->deliveryRepository->findBy([], ['scheduledAt' => 'DESC']);
    }
}
