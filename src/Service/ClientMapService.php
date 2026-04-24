<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Visit;
use App\Repository\ClientRepository;
use App\Repository\CommercialRepository;
use App\Repository\VisitRepository;
use App\Repository\ZoneRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClientMapService
{
    private const MOROCCO_CENTER = [31.85, -7.10];
    private const MOROCCO_ZOOM = 6;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CommercialRepository $commercialRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly VisitRepository $visitRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return Client[]
     */
    public function getClientsWithoutCoordinates(): array
    {
        return array_values(array_filter(
            $this->clientRepository->findForMap(),
            static fn (Client $client): bool => $client->getLatitude() === null || $client->getLongitude() === null
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageConfig(): array
    {
        return [
            'center' => self::MOROCCO_CENTER,
            'zoom' => self::MOROCCO_ZOOM,
            'filters' => [
                'commercials' => array_map(
                    static fn ($commercial): array => [
                        'id' => $commercial->getId(),
                        'name' => $commercial->getFullName(),
                    ],
                    $this->commercialRepository->findActiveOrdered()
                ),
                'zones' => array_map(
                    static fn ($zone): array => [
                        'id' => $zone->getId(),
                        'name' => $zone->getName() ?? 'Zone',
                        'city' => $zone->getCity()?->getName() ?? 'Ville',
                    ],
                    $this->zoneRepository->createQueryBuilder('zone')
                        ->leftJoin('zone.city', 'city')
                        ->addSelect('city')
                        ->orderBy('city.name', 'ASC')
                        ->addOrderBy('zone.name', 'ASC')
                        ->getQuery()
                        ->getResult()
                ),
                'cities' => $this->clientRepository->getCityOptionsForMap(),
                'types' => $this->normalizeChoices(Client::typeChoices()),
                'statuses' => $this->normalizeChoices(Client::statusChoices()),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function buildMapPayload(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $clients = $this->clientRepository->findForMap($normalizedFilters);
        $visitsByClient = $this->groupVisitsByClient($clients);

        $markers = [];
        $visibleClients = [];
        $nonLocalizableClients = [];

        foreach ($clients as $client) {
            $summary = $this->buildClientSummary($client, $visitsByClient[$client->getId() ?? 0] ?? []);
            if ($summary['is_localizable']) {
                $markers[] = $summary['marker'];
                $visibleClients[] = $summary['panel'];
                continue;
            }

            $nonLocalizableClients[] = $summary['panel'];
        }

        return [
            'filters' => $normalizedFilters,
            'summary' => [
                'total' => count($clients),
                'localized' => count($visibleClients),
                'non_localizable' => count($nonLocalizableClients),
                'missing_coordinates' => count(array_filter(
                    $clients,
                    static fn (Client $client): bool => $client->getLatitude() === null || $client->getLongitude() === null
                )),
            ],
            'map' => [
                'center' => self::MOROCCO_CENTER,
                'zoom' => self::MOROCCO_ZOOM,
                'markers' => $markers,
            ],
            'clients' => $visibleClients,
            'non_localizable_clients' => $nonLocalizableClients,
        ];
    }

    /**
     * @param array<string, string> $choices
     *
     * @return array<int, array{id:string,label:string}>
     */
    private function normalizeChoices(array $choices): array
    {
        $normalized = [];
        foreach ($choices as $label => $value) {
            $normalized[] = [
                'id' => (string) $value,
                'label' => (string) $label,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'commercial' => $this->normalizeNullableString($filters['commercial'] ?? null),
            'zone' => $this->normalizeNullableString($filters['zone'] ?? null),
            'city' => $this->normalizeNullableString($filters['city'] ?? null),
            'type' => $this->normalizeNullableString($filters['type'] ?? null),
            'status' => $this->normalizeNullableString($filters['status'] ?? null),
            'search' => trim((string) ($filters['search'] ?? '')),
            'has_active_visit' => $this->normalizeBooleanFlag($filters['has_active_visit'] ?? null),
            'has_planned_visit' => $this->normalizeBooleanFlag($filters['has_planned_visit'] ?? null),
            'assigned_to_tour' => $this->normalizeBooleanFlag($filters['assigned_to_tour'] ?? null),
            'without_tour' => $this->normalizeBooleanFlag($filters['without_tour'] ?? null),
            'without_visit' => $this->normalizeBooleanFlag($filters['without_visit'] ?? null),
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    private function normalizeBooleanFlag(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * @param Client[] $clients
     *
     * @return array<int, Visit[]>
     */
    private function groupVisitsByClient(array $clients): array
    {
        $clientIds = array_values(array_filter(array_map(
            static fn (Client $client): int => (int) ($client->getId() ?? 0),
            $clients
        )));

        $grouped = [];
        foreach ($this->visitRepository->findUnarchivedForClientIds($clientIds) as $visit) {
            $clientId = $visit->getClient()?->getId();
            if ($clientId === null) {
                continue;
            }

            $grouped[$clientId] ??= [];
            $grouped[$clientId][] = $visit;
        }

        return $grouped;
    }

    /**
     * @param Visit[] $visits
     *
     * @return array<string, mixed>
     */
    private function buildClientSummary(Client $client, array $visits): array
    {
        $clientId = (int) ($client->getId() ?? 0);
        $latestVisit = $visits[0] ?? null;
        $latestTourVisit = null;
        $hasPlannedVisit = false;
        $hasActiveVisit = false;
        $hasOpenTour = false;
        $recentVisits = [];

        foreach ($visits as $index => $visit) {
            if ($visit->getStatus() === Visit::STATUS_PLANNED) {
                $hasPlannedVisit = true;
            }

            if (in_array($visit->getStatus(), [Visit::STATUS_PLANNED, Visit::STATUS_PENDING], true)) {
                $hasActiveVisit = true;
            }

            if ($visit->getTour() !== null && $visit->getTour()?->getArchivedAt() === null) {
                $hasOpenTour = true;
                $latestTourVisit ??= $visit;
            }

            if ($index < 5) {
                $recentVisits[] = [
                    'id' => $visit->getId(),
                    'date' => $visit->getScheduledAt()?->format('d/m/Y H:i'),
                    'status' => $visit->getStatus(),
                    'status_label' => $this->humanize($visit->getStatus()),
                    'result' => $visit->getResult(),
                    'result_label' => $visit->getResult() ? $this->humanize($visit->getResult()) : 'Non renseigne',
                ];
            }
        }

        $currentTour = $latestTourVisit?->getTour();
        $code = sprintf('CL-%05d', $clientId);
        $coordinates = [
            'lat' => $client->getLatitude() !== null ? (float) $client->getLatitude() : null,
            'lng' => $client->getLongitude() !== null ? (float) $client->getLongitude() : null,
        ];
        $tone = $this->resolveTone($client, $latestVisit, $currentTour !== null);
        $canCreateVisit = !$hasActiveVisit && !$hasPlannedVisit;
        $viewTourUrl = $currentTour !== null ? $this->urlGenerator->generate('app_tour_show', ['id' => $currentTour->getId()]) : null;

        $panel = [
            'id' => $clientId,
            'code' => $code,
            'name' => $client->getName() ?? 'Client',
            'city' => $client->getCity(),
            'zone' => $client->getZone()?->getName() ?? 'Zone non renseignee',
            'commercial' => $client->getAssignedCommercial()?->getFullName() ?? 'Non affecte',
            'phone' => $client->getPhone() ?: 'Non renseigne',
            'address' => $client->getAddress() ?: 'Non renseignee',
            'status' => $client->getStatus(),
            'status_label' => $this->humanize($client->getStatus()),
            'type' => $client->getType(),
            'type_label' => $this->humanize($client->getType()),
            'visit_status' => $latestVisit?->getStatus(),
            'visit_status_label' => $latestVisit?->getStatus() ? $this->humanize($latestVisit->getStatus()) : 'Aucune visite',
            'visit_result_label' => $latestVisit?->getResult() ? $this->humanize($latestVisit->getResult()) : null,
            'tour_status' => $currentTour?->getStatus(),
            'tour_status_label' => $currentTour?->getStatus() ? $this->humanize($currentTour->getStatus()) : 'Aucune tournee',
            'has_active_visit' => $hasActiveVisit,
            'has_planned_visit' => $hasPlannedVisit,
            'has_open_tour' => $hasOpenTour,
            'tone' => $tone,
            'coordinates' => $coordinates,
            'recent_visits' => $recentVisits,
            'actions' => [
                'show_url' => $this->urlGenerator->generate('app_client_show', ['id' => $clientId]),
                'plan_visit_url' => $canCreateVisit ? $this->urlGenerator->generate('app_client_map_plan_visit', ['id' => $clientId]) : null,
                'tour_url' => $viewTourUrl,
                'tour_prepare_url' => $currentTour === null ? $this->urlGenerator->generate('app_tour_generation_prepare') : null,
            ],
        ];

        return [
            'is_localizable' => $coordinates['lat'] !== null && $coordinates['lng'] !== null,
            'marker' => [
                'id' => $clientId,
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng'],
                'tone' => $tone,
                'popup' => [
                    'title' => $panel['name'],
                    'code' => $code,
                    'city' => $panel['city'],
                    'zone' => $panel['zone'],
                    'commercial' => $panel['commercial'],
                    'visit_status' => $panel['visit_status_label'],
                    'tour_status' => $panel['tour_status_label'],
                ],
                'client' => $panel,
            ],
            'panel' => $panel,
        ];
    }

    private function resolveTone(Client $client, ?Visit $latestVisit, bool $hasOpenTour): string
    {
        if ($client->getStatus() === Client::STATUS_REFUSED) {
            return 'danger';
        }

        if (
            $latestVisit?->getResult() === Visit::RESULT_ORDER_CONFIRMED
            || in_array($client->getStatus(), [Client::STATUS_ACTIVE, Client::STATUS_LOYAL], true)
        ) {
            return 'success';
        }

        if ($hasOpenTour || $latestVisit?->getStatus() === Visit::STATUS_PLANNED) {
            return 'warning';
        }

        if ($client->getStatus() === Client::STATUS_IN_PROGRESS) {
            return 'info';
        }

        return 'primary';
    }

    private function humanize(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Non renseigne';
        }

        return ucfirst(str_replace('_', ' ', $value));
    }
}
