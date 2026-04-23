<?php

namespace App\Service;

use App\Entity\Commercial;
use App\Repository\ClientRepository;
use App\Repository\CommercialRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommercialCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommercialRepository $commercialRepository,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->commercialRepository->findBy([], ['fullName' => 'ASC']);
    }

    /**
     * @param Commercial[] $commercials
     *
     * @return array<int, int>
     */
    public function getAssignedClientCounts(array $commercials): array
    {
        $counts = [];

        foreach ($commercials as $commercial) {
            if (!$commercial instanceof Commercial || $commercial->getId() === null) {
                continue;
            }

            $counts[$commercial->getId()] = $this->clientRepository->countForCommercial($commercial);
        }

        return $counts;
    }

    public function getAssignedClients(Commercial $commercial): array
    {
        return $this->clientRepository->findForCommercial($commercial);
    }

    public function save(Commercial $commercial): void
    {
        $primaryZone = $commercial->getZones()->first() ?: null;
        $commercial->setZone($primaryZone instanceof \App\Entity\Zone ? $primaryZone : null);
        $commercial->touch();
        $this->entityManager->persist($commercial);
        $this->entityManager->flush();
    }

    public function delete(Commercial $commercial): void
    {
        $this->entityManager->remove($commercial);
        $this->entityManager->flush();
    }
}
