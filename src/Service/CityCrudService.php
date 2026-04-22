<?php

namespace App\Service;

use App\Entity\City;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;

class CityCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CityRepository $cityRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->cityRepository->findBy([], ['name' => 'ASC']);
    }

    public function save(City $city): void
    {
        $city->setName($this->normalizeName($city->getName()));
        $city->touch();
        $this->entityManager->persist($city);
        $this->entityManager->flush();
    }

    public function delete(City $city): void
    {
        $this->entityManager->remove($city);
        $this->entityManager->flush();
    }

    private function normalizeName(?string $name): string
    {
        $value = trim((string) $name);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return ucwords(mb_strtolower($value));
    }
}
