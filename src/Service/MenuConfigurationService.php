<?php

namespace App\Service;

use App\Entity\MenuVisibility;
use App\Model\MenuConfigurationData;
use App\Repository\MenuVisibilityRepository;
use Doctrine\ORM\EntityManagerInterface;

class MenuConfigurationService
{
    private const CATALOG = [
        'pilotage' => [
            'label' => 'Pilotage',
            'description' => 'Dashboard, statistiques, marche, objectifs et reunions.',
            'icon' => 'bi-bar-chart-line',
            'order' => 10,
            'children' => [
                'pilotage_dashboard' => ['label' => 'Dashboard'],
                'pilotage_stats' => ['label' => 'Statistiques'],
                'pilotage_market' => ['label' => 'Marche'],
                'pilotage_objectives' => ['label' => 'Objectifs'],
                'pilotage_meetings' => ['label' => 'Reunions'],
            ],
        ],
        'commercial' => [
            'label' => 'Commercial',
            'description' => 'Clients, equipe commerciale, visites, offres, retours terrain et satisfaction.',
            'icon' => 'bi-people',
            'order' => 20,
            'children' => [
                'commercial_clients' => ['label' => 'Clients'],
                'commercial_client_map' => ['label' => 'Carte des clients'],
                'commercial_team' => ['label' => 'Commerciaux'],
                'commercial_visits' => ['label' => 'Visites'],
                'commercial_offers' => ['label' => 'Offres'],
                'commercial_feedback' => ['label' => 'Retours terrain'],
                'commercial_satisfaction' => ['label' => 'Satisfaction'],
            ],
        ],
        'tournees' => [
            'label' => 'Tournees',
            'description' => 'Planification, execution et suivi des tournees commerciales.',
            'icon' => 'bi-signpost-split',
            'order' => 30,
            'children' => [
                'tournees_index' => ['label' => 'Liste des tournees'],
            ],
        ],
        'rdv' => [
            'label' => 'RDV',
            'description' => 'Rendez-vous pris depuis les visites commerciales.',
            'icon' => 'bi-calendar-event',
            'order' => 35,
            'children' => [
                'rdv_index' => ['label' => 'Liste des rendez-vous'],
            ],
        ],
        'achats' => [
            'label' => 'Achats',
            'description' => 'Fournisseurs, produits, consultations, imports et lancements.',
            'icon' => 'bi-box-seam',
            'order' => 40,
            'children' => [
                'achats_suppliers' => ['label' => 'Fournisseurs'],
                'achats_products' => ['label' => 'Produits'],
                'achats_launches' => ['label' => 'Lancements'],
                'achats_consultations' => ['label' => 'Consultations'],
                'achats_imports' => ['label' => 'Imports'],
            ],
        ],
        'livraisons' => [
            'label' => 'Livraisons',
            'description' => 'Suivi logistique et livraisons clients.',
            'icon' => 'bi-truck-flatbed',
            'order' => 50,
            'children' => [
                'livraisons_index' => ['label' => 'Suivi des livraisons'],
            ],
        ],
        'parametrage' => [
            'label' => 'Parametrage',
            'description' => 'Utilisateurs, villes, zones, choix et options de menu.',
            'icon' => 'bi-sliders',
            'order' => 60,
            'children' => [
                'parametrage_home' => ['label' => 'Vue generale'],
                'parametrage_users' => ['label' => 'Utilisateurs'],
                'parametrage_cities' => ['label' => 'Villes'],
                'parametrage_zones' => ['label' => 'Zones'],
                'parametrage_choices' => ['label' => 'Choix'],
                'parametrage_menu' => ['label' => 'Gestion du menu'],
            ],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MenuVisibilityRepository $menuVisibilityRepository,
    ) {
    }

    public function getFormData(): MenuConfigurationData
    {
        $this->ensureDefaults();
        $data = new MenuConfigurationData();

        foreach ($this->menuVisibilityRepository->findIndexedByCode() as $code => $setting) {
            if ($setting->isEnabled()) {
                $data->visibleMenus[] = $code;
            }
        }

        return $data;
    }

    public function save(MenuConfigurationData $data): void
    {
        $this->ensureDefaults();
        $visibleMenus = array_unique($data->visibleMenus);

        foreach ($this->menuVisibilityRepository->findIndexedByCode() as $code => $setting) {
            $setting->setEnabled(in_array($code, $visibleMenus, true));
            $this->entityManager->persist($setting);
        }

        foreach ($this->getOrderedMenuGroups() as $group) {
            $parentEnabled = in_array($group['code'], $visibleMenus, true);
            if ($parentEnabled) {
                continue;
            }

            foreach ($group['children'] as $child) {
                $setting = $this->menuVisibilityRepository->findOneBy(['code' => $child['code']]);
                if ($setting instanceof MenuVisibility) {
                    $setting->setEnabled(false);
                    $this->entityManager->persist($setting);
                }
            }
        }

        $this->entityManager->flush();
    }

    public function isVisible(string $code): bool
    {
        $this->ensureDefaults();

        $setting = $this->menuVisibilityRepository->findOneBy(['code' => $code]);

        return $setting?->isEnabled() ?? true;
    }

    public function isGroupVisible(string $groupCode): bool
    {
        $this->ensureDefaults();

        if (!$this->isVisible($groupCode)) {
            return false;
        }

        $definition = self::CATALOG[$groupCode] ?? null;
        if ($definition === null) {
            return false;
        }

        foreach (array_keys($definition['children'] ?? []) as $childCode) {
            if ($this->isVisible($childCode)) {
                return true;
            }
        }

        return false;
    }

    public function countEnabled(): int
    {
        return count(array_filter(
            $this->getOrderedMenuGroups(),
            static fn (array $group): bool => $group['enabled']
        ));
    }

    public function getMenuChoices(): array
    {
        $choices = [];

        foreach ($this->getOrderedMenuGroups() as $group) {
            $choices[$group['label']] = $group['code'];
            foreach ($group['children'] as $child) {
                $choices[$group['label'] . ' > ' . $child['label']] = $child['code'];
            }
        }

        return $choices;
    }

    /**
     * @return array<int, array{code:string,label:string,description:string,icon:string,order:int,enabled:bool,form_index:int,children:array<int, array{code:string,label:string,enabled:bool,form_index:int}>}>
     */
    public function getOrderedMenuGroups(): array
    {
        $this->ensureDefaults();
        $indexed = $this->menuVisibilityRepository->findIndexedByCode();
        $groups = [];
        $formIndex = 0;

        foreach (self::CATALOG as $groupCode => $definition) {
            $children = [];
            foreach ($definition['children'] as $childCode => $childDefinition) {
                $children[] = [
                    'code' => $childCode,
                    'label' => $childDefinition['label'],
                    'enabled' => $indexed[$childCode]?->isEnabled() ?? true,
                    'form_index' => $formIndex++,
                ];
            }

            $groups[] = [
                'code' => $groupCode,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'icon' => $definition['icon'],
                'order' => $definition['order'],
                'enabled' => $indexed[$groupCode]?->isEnabled() ?? true,
                'form_index' => $formIndex++,
                'children' => $children,
            ];
        }

        usort($groups, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        return $groups;
    }

    private function ensureDefaults(): void
    {
        $indexed = $this->menuVisibilityRepository->findIndexedByCode();
        $dirty = false;

        foreach ($this->getAllCodes() as $code) {
            if (isset($indexed[$code])) {
                continue;
            }

            $this->entityManager->persist(
                (new MenuVisibility())
                    ->setCode($code)
                    ->setEnabled(true)
            );
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return string[]
     */
    private function getAllCodes(): array
    {
        $codes = [];

        foreach (self::CATALOG as $groupCode => $definition) {
            $codes[] = $groupCode;
            foreach (array_keys($definition['children']) as $childCode) {
                $codes[] = $childCode;
            }
        }

        return $codes;
    }
}
