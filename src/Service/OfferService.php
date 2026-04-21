<?php

namespace App\Service;

use App\Repository\OfferRepository;

class OfferService
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
    ) {
    }

    public function getLatestOffers(): array
    {
        return $this->offerRepository->findLatest();
    }

    public function getDashboardStats(): array
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');

        return [
            'in_progress' => $this->offerRepository->count(['status' => 'en_cours']),
            'revenue' => $this->offerRepository->sumCurrentMonth($startOfMonth),
        ];
    }
}
