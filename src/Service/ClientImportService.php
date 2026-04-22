<?php

namespace App\Service;

use App\Entity\City;
use App\Entity\Client;
use App\Entity\Zone;
use App\Repository\CityRepository;
use App\Repository\ClientRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ClientImportService
{
    private const DEFAULT_BATCH_SIZE = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly CityRepository $cityRepository,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function prepareImport(UploadedFile $file): array
    {
        $rows = $this->extractRows($file);
        $total = count($rows);

        if ($total === 0) {
            throw new \RuntimeException('Aucune ligne exploitable n a ete detectee dans le fichier.');
        }

        $token = bin2hex(random_bytes(12));
        $state = [
            'rows' => $rows,
            'total' => $total,
            'stats' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ],
            'preparedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        $this->writeState($token, $state);

        return [
            'token' => $token,
            'total' => $total,
            'batchSize' => self::DEFAULT_BATCH_SIZE,
        ];
    }

    public function processBatch(string $token, int $offset = 0, int $limit = self::DEFAULT_BATCH_SIZE): array
    {
        $state = $this->readState($token);
        $rows = $state['rows'] ?? [];
        $total = (int) ($state['total'] ?? count($rows));
        $stats = $state['stats'] ?? ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $slice = array_slice($rows, $offset, $limit);
        $logs = [];

        foreach ($slice as $index => $row) {
            $position = $offset + $index + 1;

            try {
                $result = $this->importRow($row);
                $stats[$result['action']]++;
                $logs[] = [
                    'level' => 'success',
                    'message' => sprintf(
                        'Ligne %d/%d : %s %s.',
                        $position,
                        $total,
                        $result['action'] === 'created' ? 'prospect cree' : 'prospect mis a jour',
                        $result['label']
                    ),
                ];
            } catch (\Throwable $exception) {
                $stats['skipped']++;
                $logs[] = [
                    'level' => 'warning',
                    'message' => sprintf('Ligne %d/%d : %s', $position, $total, $exception->getMessage()),
                ];

                $this->logger->warning('Import prospect skipped.', [
                    'row' => $row,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $processed = min($offset + count($slice), $total);
        $done = $processed >= $total;

        $state['stats'] = $stats;

        if ($done) {
            $this->deleteState($token);
        } else {
            $this->writeState($token, $state);
        }

        return [
            'success' => true,
            'logs' => $logs,
            'processed' => $processed,
            'total' => $total,
            'nextOffset' => $processed,
            'done' => $done,
            'stats' => $stats,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray('', true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (!$rows) {
            return [];
        }

        $headerRow = array_shift($rows);
        $headerMap = $this->buildHeaderMap($headerRow ?: []);
        $normalizedRows = [];

        foreach ($rows as $row) {
            if (!$this->rowHasData($row)) {
                continue;
            }

            $normalizedRows[] = [
                'name' => $this->valueFor($row, $headerMap, ['name', 'nom', 'client', 'prospect', 'clinique']),
                'city' => $this->valueFor($row, $headerMap, ['city', 'ville']),
                'zone' => $this->valueFor($row, $headerMap, ['zone', 'secteur']),
                'type' => $this->valueFor($row, $headerMap, ['type', 'categorie']),
                'email' => $this->valueFor($row, $headerMap, ['email', 'mail']),
                'phone' => $this->valueFor($row, $headerMap, ['phone', 'telephone', 'tel', 'mobile']),
                'address' => $this->valueFor($row, $headerMap, ['address', 'adresse']),
                'annualRevenue' => $this->valueFor($row, $headerMap, ['annualrevenue', 'caannuel', 'ca', 'chiffredaffaires']),
                'notes' => $this->valueFor($row, $headerMap, ['notes', 'note', 'commentaire', 'commentaires']),
            ];
        }

        return $normalizedRows;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{action: 'created'|'updated', label: string}
     */
    private function importRow(array $row): array
    {
        $name = $this->sanitizeText($row['name'] ?? null);
        $address = $this->sanitizeText($row['address'] ?? null);
        $phone = $this->sanitizeText($row['phone'] ?? null);
        $zone = $this->findZone($this->sanitizeText($row['zone'] ?? null), $this->sanitizeText($row['city'] ?? null));
        $cityName = $zone?->getCity()?->getName() ?? $this->resolveCityName($this->sanitizeText($row['city'] ?? null));

        if ($name === null) {
            throw new \RuntimeException('Nom du prospect manquant.');
        }

        if ($address === null) {
            throw new \RuntimeException(sprintf('Adresse postale obligatoire pour %s.', $name));
        }

        if ($cityName === null) {
            throw new \RuntimeException(sprintf('Ville manquante pour %s.', $name));
        }

        $client = $this->findExistingClient($name, $cityName, $phone);
        $isNew = $client === null;

        if ($client === null) {
            $client = new Client();
            $client->setPotentialScore(60);
            $client->setSolvencyScore(60);
            $client->setSegment(Client::SEGMENT_STANDARD);
        }

        $client->setName($name);
        $client->setAddress($address);
        $client->setCity($cityName);
        $client->setZone($zone);
        $client->setType($this->normalizeType($this->sanitizeText($row['type'] ?? null)));
        $client->setEmail($this->sanitizeText($row['email'] ?? null));
        $client->setPhone($phone);
        $client->setAnnualRevenue($this->normalizeMoney($row['annualRevenue'] ?? null));
        $client->setNotes($this->sanitizeText($row['notes'] ?? null));

        if ($client->getStatus() === null || in_array($client->getStatus(), [Client::STATUS_POTENTIAL, Client::STATUS_IN_PROGRESS], true)) {
            $client->setStatus(Client::STATUS_POTENTIAL);
        }

        $client->touch();

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return [
            'action' => $isNew ? 'created' : 'updated',
            'label' => $client->getName() ?? 'Prospect',
        ];
    }

    private function findExistingClient(string $name, string $cityName, ?string $phone): ?Client
    {
        if ($phone !== null) {
            $existing = $this->clientRepository->findOneBy([
                'name' => $name,
                'phone' => $phone,
            ]);

            if ($existing instanceof Client) {
                return $existing;
            }
        }

        $existing = $this->clientRepository->findOneBy([
            'name' => $name,
            'city' => $cityName,
        ]);

        return $existing instanceof Client ? $existing : null;
    }

    private function findZone(?string $zoneName, ?string $cityName): ?Zone
    {
        if ($zoneName === null) {
            return null;
        }

        $queryBuilder = $this->zoneRepository->createQueryBuilder('zone')
            ->leftJoin('zone.city', 'city')
            ->addSelect('city')
            ->andWhere('LOWER(zone.name) = :zoneName')
            ->setParameter('zoneName', $this->normalizeLookup($zoneName))
            ->setMaxResults(1);

        if ($cityName !== null) {
            $queryBuilder
                ->andWhere('LOWER(city.name) = :cityName')
                ->setParameter('cityName', $this->normalizeLookup($cityName));
        }

        $zone = $queryBuilder->getQuery()->getOneOrNullResult();

        return $zone instanceof Zone ? $zone : null;
    }

    private function resolveCityName(?string $cityName): ?string
    {
        if ($cityName === null) {
            return null;
        }

        $city = $this->cityRepository->createQueryBuilder('city')
            ->andWhere('LOWER(city.name) = :name')
            ->setParameter('name', $this->normalizeLookup($cityName))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($city instanceof City) {
            return $city->getName();
        }

        return $this->titleize($cityName);
    }

    /**
     * @param array<int|string, mixed> $headerRow
     *
     * @return array<string, string>
     */
    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $column => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $map[$normalized] = (string) $column;
            }
        }

        return $map;
    }

    /**
     * @param array<int|string, mixed> $row
     * @param array<string, string> $headerMap
     * @param string[] $aliases
     */
    private function valueFor(array $row, array $headerMap, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $column = $headerMap[$alias] ?? null;
            if ($column === null) {
                continue;
            }

            return $this->sanitizeText($row[$column] ?? null);
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $row
     */
    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->sanitizeText($value) !== null) {
                return true;
            }
        }

        return false;
    }

    private function normalizeType(?string $value): string
    {
        return match ($this->normalizeLookup($value)) {
            'hopital', 'hôpital' => Client::TYPE_HOSPITAL,
            'pharmacie' => Client::TYPE_PHARMACY,
            'laboratoire', 'labo' => Client::TYPE_LAB,
            default => Client::TYPE_CLINIC,
        };
    }

    private function normalizeMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $normalized = preg_replace('/[^0-9,.-]/', '', (string) $value) ?? '0';
        $normalized = str_replace(',', '.', $normalized);

        return number_format((float) $normalized, 2, '.', '');
    }

    private function sanitizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->normalizeLookup($value);

        return str_replace([' ', '-', '_', '\'', '"'], '', $value);
    }

    private function normalizeLookup(?string $value): string
    {
        $value ??= '';
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $normalized = $normalized === false ? $value : $normalized;

        return mb_strtolower(trim($normalized));
    }

    private function titleize(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function getImportDirectory(): string
    {
        $directory = $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'client-imports';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }

    private function getStatePath(string $token): string
    {
        return $this->getImportDirectory() . DIRECTORY_SEPARATOR . $token . '.json';
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(string $token, array $state): void
    {
        file_put_contents($this->getStatePath($token), json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(string $token): array
    {
        $path = $this->getStatePath($token);

        if (!is_file($path)) {
            throw new \RuntimeException('Session d import introuvable. Recharge le fichier puis relance l import.');
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException('Impossible de lire la session d import.');
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function deleteState(string $token): void
    {
        $path = $this->getStatePath($token);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
