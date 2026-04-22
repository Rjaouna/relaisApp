<?php

namespace App\Service;

use App\Entity\Zone;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;

class ZoneCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->zoneRepository->createQueryBuilder('zone')
            ->leftJoin('zone.city', 'city')
            ->addSelect('city')
            ->orderBy('city.name', 'ASC')
            ->addOrderBy('zone.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Zone $zone): void
    {
        $zone->setCode($this->generateCodeFromName($zone->getName()));
        $zone->touch();
        $this->entityManager->persist($zone);
        $this->entityManager->flush();
    }

    public function delete(Zone $zone): void
    {
        $this->entityManager->remove($zone);
        $this->entityManager->flush();
    }

    private function generateCodeFromName(?string $name): string
    {
        $value = trim((string) $name);
        if ($value === '') {
            return '';
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($normalized === false) {
            $normalized = $value;
        }

        $normalized = strtoupper($normalized);
        $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';

        return substr($normalized, 0, 20);
    }
}
