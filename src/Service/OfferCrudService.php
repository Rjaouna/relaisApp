<?php

namespace App\Service;

use App\Entity\Offer;
use App\Entity\OfferItem;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;

class OfferCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferRepository $offerRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->offerRepository->findBy([], ['issuedAt' => 'DESC']);
    }

    public function save(Offer $offer): void
    {
        $this->synchronizeItems($offer);
        $offer->setAmount($this->calculateOfferTotal($offer));
        $offer->touch();
        $this->entityManager->persist($offer);
        $this->entityManager->flush();
    }

    public function delete(Offer $offer): void
    {
        $this->entityManager->remove($offer);
        $this->entityManager->flush();
    }

    private function synchronizeItems(Offer $offer): void
    {
        foreach ($offer->getItems()->toArray() as $item) {
            if (!$item instanceof OfferItem) {
                continue;
            }

            if ($item->getProduct() === null) {
                $offer->removeItem($item);
                continue;
            }

            if ((float) $item->getUnitPrice() <= 0) {
                $item->setUnitPrice($item->getProduct()->getSalePrice());
            }

            $quantity = max(1, $item->getQuantity());
            $item->setQuantity($quantity);
            $lineTotal = $quantity * (float) $item->getUnitPrice();
            $item->setLineTotal(number_format($lineTotal, 2, '.', ''));
            $item->setOffer($offer);
        }
    }

    private function calculateOfferTotal(Offer $offer): string
    {
        $total = 0.0;
        foreach ($offer->getItems() as $item) {
            $total += (float) $item->getLineTotal();
        }

        return number_format($total, 2, '.', '');
    }
}
