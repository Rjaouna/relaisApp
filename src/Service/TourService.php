<?php

namespace App\Service;

use App\Repository\TourRepository;

class TourService
{
    public function __construct(
        private readonly TourRepository $tourRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->tourRepository->findUpcomingTours();
    }
}
