<?php

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    /**
     * @return Client[]
     */
    public function getListing(): array
    {
        return $this->clientRepository->findForListing();
    }

    public function getGroupedListings(): array
    {
        $clients = $this->getListing();

        return [
            'prospection' => array_values(array_filter($clients, static fn (Client $client): bool => in_array($client->getStatus(), [
                Client::STATUS_POTENTIAL,
                Client::STATUS_IN_PROGRESS,
            ], true))),
            'confirmed' => array_values(array_filter($clients, static fn (Client $client): bool => $client->getStatus() === Client::STATUS_ACTIVE)),
            'refused' => array_values(array_filter($clients, static fn (Client $client): bool => $client->getStatus() === Client::STATUS_REFUSED)),
        ];
    }

    public function save(Client $client): void
    {
        $client->touch();
        $this->entityManager->persist($client);
        $this->entityManager->flush();
    }

    public function delete(Client $client): void
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();
    }

    public function getCounters(): array
    {
        $grouped = $this->getGroupedListings();

        return [
            'total' => $this->clientRepository->count([]),
            'active' => $this->clientRepository->countByStatus(Client::STATUS_ACTIVE),
            'potential' => $this->clientRepository->countByStatus(Client::STATUS_POTENTIAL),
            'in_progress' => $this->clientRepository->countByStatus(Client::STATUS_IN_PROGRESS),
            'prospection' => count($grouped['prospection']),
            'confirmed' => count($grouped['confirmed']),
            'refused' => $this->clientRepository->countByStatus(Client::STATUS_REFUSED),
        ];
    }
}
