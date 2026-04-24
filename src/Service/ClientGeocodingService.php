<?php

namespace App\Service;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class ClientGeocodingService
{
    /**
     * @var array<string, array{lat: float, lng: float}>
     */
    private const MOROCCO_CITY_COORDINATES = [
        'casablanca' => ['lat' => 33.5731, 'lng' => -7.5898],
        'rabat' => ['lat' => 34.0209, 'lng' => -6.8416],
        'marrakech' => ['lat' => 31.6295, 'lng' => -7.9811],
        'fes' => ['lat' => 34.0331, 'lng' => -5.0003],
        'fès' => ['lat' => 34.0331, 'lng' => -5.0003],
        'tanger' => ['lat' => 35.7595, 'lng' => -5.8340],
        'tangier' => ['lat' => 35.7595, 'lng' => -5.8340],
        'agadir' => ['lat' => 30.4278, 'lng' => -9.5981],
        'meknes' => ['lat' => 33.8935, 'lng' => -5.5473],
        'mèknes' => ['lat' => 33.8935, 'lng' => -5.5473],
        'oujda' => ['lat' => 34.6814, 'lng' => -1.9086],
        'kenitra' => ['lat' => 34.2610, 'lng' => -6.5802],
        'kénitra' => ['lat' => 34.2610, 'lng' => -6.5802],
        'tetouan' => ['lat' => 35.5889, 'lng' => -5.3626],
        'tétouan' => ['lat' => 35.5889, 'lng' => -5.3626],
        'el jadida' => ['lat' => 33.2316, 'lng' => -8.5007],
        'safi' => ['lat' => 32.2994, 'lng' => -9.2372],
        'nador' => ['lat' => 35.1744, 'lng' => -2.9287],
        'laayoune' => ['lat' => 27.1536, 'lng' => -13.2033],
        'laâyoune' => ['lat' => 27.1536, 'lng' => -13.2033],
        'dakhla' => ['lat' => 23.6848, 'lng' => -15.9570],
        'beni mellal' => ['lat' => 32.3373, 'lng' => -6.3498],
        'béni mellal' => ['lat' => 32.3373, 'lng' => -6.3498],
        'khouribga' => ['lat' => 32.8811, 'lng' => -6.9063],
        'temara' => ['lat' => 33.9287, 'lng' => -6.9066],
        'témara' => ['lat' => 33.9287, 'lng' => -6.9066],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param Client[] $clients
     *
     * @return array{updated:int, skipped:int}
     */
    public function geocodeMissingClients(array $clients): array
    {
        $updated = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            if (!$client instanceof Client) {
                continue;
            }

            if ($client->getLatitude() !== null && $client->getLongitude() !== null) {
                ++$skipped;
                continue;
            }

            $coordinates = $this->resolveCoordinates($client);
            if ($coordinates === null) {
                ++$skipped;
                continue;
            }

            $client
                ->setLatitude(number_format($coordinates['lat'], 7, '.', ''))
                ->setLongitude(number_format($coordinates['lng'], 7, '.', ''));
            $client->touch();

            $this->entityManager->persist($client);
            ++$updated;
        }

        $this->entityManager->flush();

        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function resolveCoordinates(Client $client): ?array
    {
        $city = $this->normalizeKey($client->getCity());
        if ($city === null || !isset(self::MOROCCO_CITY_COORDINATES[$city])) {
            return null;
        }

        $base = self::MOROCCO_CITY_COORDINATES[$city];
        $seed = $this->normalizeKey($client->getAddress()) ?? $this->normalizeKey($client->getName()) ?? (string) $client->getId();
        $hash = abs(crc32($seed));

        $latOffset = (($hash % 900) / 100000) - 0.0045;
        $lngOffset = (((int) floor($hash / 1000) % 900) / 100000) - 0.0045;

        return [
            'lat' => $base['lat'] + $latOffset,
            'lng' => $base['lng'] + $lngOffset,
        ];
    }

    private function normalizeKey(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($normalized === false) {
            $normalized = $value;
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
