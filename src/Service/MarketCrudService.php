<?php

namespace App\Service;

use App\Entity\Market;
use App\Repository\MarketRepository;
use Doctrine\ORM\EntityManagerInterface;

class MarketCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarketRepository $marketRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->marketRepository->findBy([], ['globalScore' => 'DESC']);
    }

    public function save(Market $market): void
    {
        $market
            ->setClientsCount(max(0, $market->getClientsCount()))
            ->setRevenue(number_format((float) $market->getRevenue(), 2, '.', ''))
            ->setCompetitionScore(max(0, $market->getCompetitionScore()))
            ->setCoverageScore(max(0, $market->getCoverageScore()))
            ->setGlobalScore(max(0, $market->getGlobalScore()));

        $this->entityManager->persist($market);
        $this->entityManager->flush();
    }

    public function delete(Market $market): void
    {
        $this->entityManager->remove($market);
        $this->entityManager->flush();
    }

    public function findOneByCity(string $city): ?Market
    {
        return $this->marketRepository->findOneBy(['city' => $city]);
    }
}
