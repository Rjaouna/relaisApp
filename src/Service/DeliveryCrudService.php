<?php

namespace App\Service;

use App\Entity\Delivery;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeliveryCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeliveryRepository $deliveryRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->deliveryRepository->findBy([], ['scheduledAt' => 'DESC']);
    }

    public function save(Delivery $delivery): void
    {
        $this->entityManager->persist($delivery);
        $this->entityManager->flush();
    }

    public function delete(Delivery $delivery): void
    {
        $this->entityManager->remove($delivery);
        $this->entityManager->flush();
    }
}
