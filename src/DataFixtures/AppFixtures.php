<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\City;
use App\Entity\Commercial;
use App\Entity\Delivery;
use App\Entity\Market;
use App\Entity\Objective;
use App\Entity\Offer;
use App\Entity\OfferItem;
use App\Entity\Product;
use App\Entity\ReferenceOption;
use App\Entity\Supplier;
use App\Entity\SupplyOrder;
use App\Entity\Tour;
use App\Entity\User;
use App\Entity\Visit;
use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_PASSWORD = 'Relais2026!';
    private const MOROCCO_CITY_COORDINATES = [
        'Casablanca' => ['lat' => 33.5731, 'lng' => -7.5898],
        'Rabat' => ['lat' => 34.0209, 'lng' => -6.8416],
        'Marrakech' => ['lat' => 31.6295, 'lng' => -7.9811],
        'Fes' => ['lat' => 34.0331, 'lng' => -5.0003],
        'Tanger' => ['lat' => 35.7595, 'lng' => -5.8340],
        'Agadir' => ['lat' => 30.4278, 'lng' => -9.5981],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createReferenceOptions($manager);
        $cities = $this->createCities($manager);
        $zones = $this->createZones($manager, $cities);
        $users = $this->createUsers($manager);
        $commercials = $this->createCommercials($manager, $zones, $users);
        $clients = $this->createClients($manager, $commercials, $zones);
        $this->createVisits($manager, $clients);
        $this->createTours($manager, $commercials);
        $this->createOffers($manager, $clients);
        $suppliers = $this->createSuppliers($manager);
        $this->createProducts($manager, $suppliers);
        $this->createSupplyOrders($manager, $suppliers);
        $this->createDeliveries($manager, $clients);
        $this->createObjectives($manager, $commercials);
        $this->createMarkets($manager);

        $manager->flush();
    }

    private function createReferenceOptions(ObjectManager $manager): void
    {
        $definitions = [
            ReferenceOption::CATEGORY_CLIENT_TYPE => [
                ['Clinique', Client::TYPE_CLINIC, 10],
                ['Hopital', Client::TYPE_HOSPITAL, 20],
                ['Pharmacie', Client::TYPE_PHARMACY, 30],
                ['Laboratoire', Client::TYPE_LAB, 40],
            ],
            ReferenceOption::CATEGORY_CLIENT_STATUS => [
                ['Potentiel', Client::STATUS_POTENTIAL, 10],
                ['En cours', Client::STATUS_IN_PROGRESS, 20],
                ['Actif', Client::STATUS_ACTIVE, 30],
                ['Client fidele', Client::STATUS_LOYAL, 40],
                ['Refuse', Client::STATUS_REFUSED, 50],
            ],
            ReferenceOption::CATEGORY_CLIENT_SEGMENT => [
                ['Standard', Client::SEGMENT_STANDARD, 10],
                ['Developpement', Client::SEGMENT_DEVELOPMENT, 20],
                ['Premium', Client::SEGMENT_PREMIUM, 30],
                ['Strategique', Client::SEGMENT_STRATEGIC, 40],
            ],
            ReferenceOption::CATEGORY_CLIENT_POTENTIAL_LEVEL => [
                ['Tres faible', 20, 10],
                ['Faible', 40, 20],
                ['Moyen', 60, 30],
                ['Bon', 80, 40],
                ['Tres bon', 100, 50],
            ],
            ReferenceOption::CATEGORY_CLIENT_SOLVENCY_LEVEL => [
                ['Tres faible', 20, 10],
                ['Faible', 40, 20],
                ['Moyen', 60, 30],
                ['Bon', 80, 40],
                ['Tres bon', 100, 50],
            ],
            ReferenceOption::CATEGORY_VISIT_TYPE => [
                ['Prospection', 'prospection', 10],
                ['Recouvrement', 'recouvrement', 20],
                ['Demonstration produit', 'demonstration', 30],
                ['Veille', 'veille', 40],
                ['Contrat SAV', 'sav', 50],
                ['Courtoisie', 'courtoisie', 60],
            ],
            ReferenceOption::CATEGORY_VISIT_PRIORITY => [
                ['Haute', 'haute', 10],
                ['Moyenne', 'moyenne', 20],
                ['Basse', 'basse', 30],
            ],
            ReferenceOption::CATEGORY_VISIT_STATUS => [
                ['Prevue', Visit::STATUS_PLANNED, 10],
                ['Realisee', Visit::STATUS_COMPLETED, 20],
                ['En attente', Visit::STATUS_PENDING, 30],
                ['Annulee', Visit::STATUS_CANCELLED, 40],
            ],
            ReferenceOption::CATEGORY_VISIT_RESULT => [
                ['Absent', Visit::RESULT_ABSENT, 10],
                ['Pas interesse', Visit::RESULT_NOT_INTERESTED, 20],
                ['RDV pris', Visit::RESULT_APPOINTMENT_BOOKED, 30],
                ['Client confirme', Visit::RESULT_CLIENT_CONFIRMED, 40],
                ['Devis envoye', Visit::RESULT_QUOTE_SENT, 50],
                ['Commande confirmee', Visit::RESULT_ORDER_CONFIRMED, 60],
                ['A relancer', Visit::RESULT_FOLLOW_UP, 70],
            ],
            ReferenceOption::CATEGORY_OFFER_STATUS => [
                ['En cours', 'en_cours', 10],
                ['Acceptee', 'acceptee', 20],
                ['Refusee', 'refusee', 30],
                ['Brouillon', 'brouillon', 40],
            ],
            ReferenceOption::CATEGORY_TOUR_STATUS => [
                ['Programmee', Tour::STATUS_PROGRAMMED, 10],
                ['En cours', Tour::STATUS_IN_PROGRESS, 20],
                ['Terminee', Tour::STATUS_COMPLETED, 30],
                ['Annulee', Tour::STATUS_CANCELLED, 40],
            ],
            ReferenceOption::CATEGORY_MARKET_ZONE_STATUS => [
                ['Saturee', 'saturee', 10],
                ['A developper', 'a_developper', 20],
                ['Opportunite', 'opportunite', 30],
            ],
            ReferenceOption::CATEGORY_DELIVERY_STATUS => [
                ['Planifiee', 'planifiee', 10],
                ['En cours', 'en_cours', 20],
                ['Livree', 'livree', 30],
                ['En retard', 'en_retard', 40],
            ],
            ReferenceOption::CATEGORY_SUPPLIER_STATUS => [
                ['Valide', 'valide', 10],
                ['Preselectionne', 'preselectionne', 20],
                ['A evaluer', 'a_evaluer', 30],
            ],
            ReferenceOption::CATEGORY_PRODUCT_CATEGORY => [
                ['Imagerie', 'Imagerie', 10],
                ['Monitoring', 'Monitoring', 20],
                ['Consommable', 'Consommable', 30],
            ],
            ReferenceOption::CATEGORY_PRODUCT_MARKET_STATUS => [
                ['Standard', 'standard', 10],
                ['Innovation', 'innovation', 20],
                ['En lancement', 'en_lancement', 30],
            ],
            ReferenceOption::CATEGORY_SUPPLY_ORDER_STATUS => [
                ['En attente', 'en_attente', 10],
                ['En transit', 'en_transit', 20],
                ['Livree', 'livree', 30],
                ['Bloquee', 'bloquee', 40],
            ],
        ];

        foreach ($definitions as $category => $options) {
            foreach ($options as [$label, $value, $sortOrder]) {
                $option = new ReferenceOption();
                $option
                    ->setCategory($category)
                    ->setLabel($label)
                    ->setValue($value)
                    ->setSortOrder($sortOrder)
                    ->setIsActive(true);

                $manager->persist($option);
            }
        }
    }

    /**
     * @return array<string, City>
     */
    private function createCities(ObjectManager $manager): array
    {
        $cities = [];

        foreach (['Casablanca', 'Rabat', 'Marrakech', 'Fes', 'Tanger', 'Agadir'] as $name) {
            $city = new City();
            $city
                ->setName($name)
                ->setIsActive(true);

            $manager->persist($city);
            $cities[strtolower($name)] = $city;
        }

        return $cities;
    }

    /**
     * @return array<string, Zone>
     */
    private function createZones(ObjectManager $manager, array $cities): array
    {
        $zones = [];

        $definitions = [
            'casa_centre' => ['Casablanca Centre', $cities['casablanca'], 'CAS-CEN', 'Zone a forte densite clinique et hospitaliere.'],
            'rabat_nord' => ['Rabat Nord', $cities['rabat'], 'RAB-NOR', 'Zone de developpement pour les comptes publics et laboratoires.'],
            'marrakech_medina' => ['Marrakech Medina', $cities['marrakech'], 'MAR-MED', 'Zone mixte privee pour prospection clinique et demonstration.'],
            'fes_atlas' => ['Fes Atlas', $cities['fes'], 'FES-ATL', 'Zone a fort potentiel laboratoire et hopital prive.'],
            'tanger_port' => ['Tanger Port', $cities['tanger'], 'TAN-POR', 'Zone nord strategique pour comptes multisites.'],
            'agadir_sud' => ['Agadir Sud', $cities['agadir'], 'AGA-SUD', 'Zone de developpement sur cliniques et pharmacies premium.'],
        ];

        foreach ($definitions as $key => [$name, $city, $code, $notes]) {
            $zone = new Zone();
            $zone
                ->setName($name)
                ->setCity($city)
                ->setCode($code)
                ->setIsActive(true)
                ->setNotes($notes);

            $manager->persist($zone);
            $zones[$key] = $zone;
        }

        return $zones;
    }

    /**
     * @return array<string, User>
     */
    private function createUsers(ObjectManager $manager): array
    {
        $users = [];

        $definitions = [
            'admin' => ['admin@relais-medical.local', 'Administrateur Relais Medical', ['ROLE_ADMIN']],
            'direction' => ['direction@relais-medical.local', 'Directrice Commerciale', ['ROLE_DIRECTION']],
            'commercial' => ['commercial@relais-medical.local', 'Ahmed Commercial', ['ROLE_COMMERCIAL']],
            'achat' => ['achat@relais-medical.local', 'Responsable Achat', ['ROLE_ACHAT']],
            'logistique' => ['logistique@relais-medical.local', 'Responsable Logistique', ['ROLE_LOGISTIQUE']],
        ];

        foreach ($definitions as $key => [$email, $fullName, $roles]) {
            $user = new User();
            $user
                ->setEmail($email)
                ->setFullName($fullName)
                ->setRoles($roles)
                ->setIsActive(true)
                ->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));

            $manager->persist($user);
            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @param array<string, Zone> $zones
     * @param array<string, User> $users
     * @return array<string, Commercial>
     */
    private function createCommercials(ObjectManager $manager, array $zones, array $users): array
    {
        $commercials = [];

        $ahmed = new Commercial();
        $ahmed
            ->setFullName('Ahmed El Idrissi')
            ->setCity('Casablanca')
            ->setZone($zones['casa_centre'])
            ->setSalesTarget(500000)
            ->setVisitsTarget(35)
            ->setNewClientsTarget(8)
            ->setCurrentClientsLoad(4)
            ->setCurrentVisitsLoad(6)
            ->setIsActive(true)
            ->setUser($users['commercial']);
        $ahmed
            ->addZone($zones['casa_centre'])
            ->addZone($zones['tanger_port'])
            ->addZone($zones['rabat_nord']);

        $manager->persist($ahmed);
        $commercials['ahmed'] = $ahmed;

        $salma = new Commercial();
        $salma
            ->setFullName('Salma Benyoussef')
            ->setCity('Rabat')
            ->setZone($zones['rabat_nord'])
            ->setSalesTarget(420000)
            ->setVisitsTarget(28)
            ->setNewClientsTarget(6)
            ->setCurrentClientsLoad(3)
            ->setCurrentVisitsLoad(4)
            ->setIsActive(true);
        $salma
            ->addZone($zones['rabat_nord'])
            ->addZone($zones['fes_atlas'])
            ->addZone($zones['marrakech_medina'])
            ->addZone($zones['agadir_sud']);

        $manager->persist($salma);
        $commercials['salma'] = $salma;

        return $commercials;
    }

    /**
     * @param array<string, Commercial> $commercials
     * @param array<string, Zone> $zones
     * @return array<string, Client>
     */
    private function createClients(ObjectManager $manager, array $commercials, array $zones): array
    {
        $clients = [];

        $definitions = [
            'clinic_alfirane' => [
                'Clinique Al Firane', 'Casablanca', $zones['casa_centre'], Client::TYPE_CLINIC, Client::STATUS_ACTIVE,
                'contact@alfirane.ma', '0522456789', 'Bd Anfa, Casablanca', 92, 88, 'premium', '120000.00',
                'Client prioritaire, fort potentiel IRM.', $commercials['ahmed'],
            ],
            'pharmacie_sante' => [
                'Pharmacie Sante+', 'Casablanca', $zones['casa_centre'], Client::TYPE_PHARMACY, Client::STATUS_IN_PROGRESS,
                'contact@pharmaciesante.ma', '0522334455', 'Maarif, Casablanca', 68, 75, 'standard', '45000.00',
                'Interessee par les consommables et la demonstration produit.', $commercials['ahmed'],
            ],
            'lab_amal' => [
                'Laboratoire Al Amal', 'Rabat', $zones['rabat_nord'], Client::TYPE_LAB, Client::STATUS_POTENTIAL,
                'contact@alamal.ma', '0537123456', 'Agdal, Rabat', 80, 70, 'developpement', '60000.00',
                'Prospect a relancer apres etude budgetaire.', $commercials['salma'],
            ],
        ];

        $seedDefinitions = [
            ['Hopital Atlas Care', 'Casablanca', 'casa_centre', Client::TYPE_HOSPITAL, Client::STATUS_ACTIVE],
            ['Clinique Noor', 'Casablanca', 'casa_centre', Client::TYPE_CLINIC, Client::STATUS_IN_PROGRESS],
            ['Pharmacie Ocean', 'Casablanca', 'casa_centre', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Vita Scan', 'Casablanca', 'casa_centre', Client::TYPE_LAB, Client::STATUS_ACTIVE],
            ['Clinique Les Palmiers', 'Rabat', 'rabat_nord', Client::TYPE_CLINIC, Client::STATUS_IN_PROGRESS],
            ['Hopital Al Qods', 'Rabat', 'rabat_nord', Client::TYPE_HOSPITAL, Client::STATUS_ACTIVE],
            ['Pharmacie Marina', 'Rabat', 'rabat_nord', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Bio Rabat', 'Rabat', 'rabat_nord', Client::TYPE_LAB, Client::STATUS_IN_PROGRESS],
            ['Clinique Menara', 'Marrakech', 'marrakech_medina', Client::TYPE_CLINIC, Client::STATUS_ACTIVE],
            ['Hopital Oasis', 'Marrakech', 'marrakech_medina', Client::TYPE_HOSPITAL, Client::STATUS_IN_PROGRESS],
            ['Pharmacie Koutoubia', 'Marrakech', 'marrakech_medina', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Atlas Med', 'Marrakech', 'marrakech_medina', Client::TYPE_LAB, Client::STATUS_ACTIVE],
            ['Clinique Saiss', 'Fes', 'fes_atlas', Client::TYPE_CLINIC, Client::STATUS_IN_PROGRESS],
            ['Hopital Andalou', 'Fes', 'fes_atlas', Client::TYPE_HOSPITAL, Client::STATUS_ACTIVE],
            ['Pharmacie Medina Fes', 'Fes', 'fes_atlas', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Fassi', 'Fes', 'fes_atlas', Client::TYPE_LAB, Client::STATUS_IN_PROGRESS],
            ['Clinique Detroit', 'Tanger', 'tanger_port', Client::TYPE_CLINIC, Client::STATUS_ACTIVE],
            ['Hopital Cap Spartel', 'Tanger', 'tanger_port', Client::TYPE_HOSPITAL, Client::STATUS_IN_PROGRESS],
            ['Pharmacie Malabata', 'Tanger', 'tanger_port', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Nord Scan', 'Tanger', 'tanger_port', Client::TYPE_LAB, Client::STATUS_ACTIVE],
            ['Clinique Souss', 'Agadir', 'agadir_sud', Client::TYPE_CLINIC, Client::STATUS_IN_PROGRESS],
            ['Hopital Tildi', 'Agadir', 'agadir_sud', Client::TYPE_HOSPITAL, Client::STATUS_ACTIVE],
            ['Pharmacie Corniche', 'Agadir', 'agadir_sud', Client::TYPE_PHARMACY, Client::STATUS_POTENTIAL],
            ['Laboratoire Oceanis', 'Agadir', 'agadir_sud', Client::TYPE_LAB, Client::STATUS_IN_PROGRESS],
            ['Clinique Horizon', 'Casablanca', 'casa_centre', Client::TYPE_CLINIC, Client::STATUS_ACTIVE],
            ['Pharmacie Centrale Plus', 'Rabat', 'rabat_nord', Client::TYPE_PHARMACY, Client::STATUS_IN_PROGRESS],
            ['Laboratoire Majorelle', 'Marrakech', 'marrakech_medina', Client::TYPE_LAB, Client::STATUS_POTENTIAL],
        ];

        foreach ($seedDefinitions as $index => [$name, $city, $zoneKey, $type, $status]) {
            $commercial = in_array($city, ['Casablanca', 'Tanger'], true) ? $commercials['ahmed'] : $commercials['salma'];
            $segment = match ($status) {
                Client::STATUS_ACTIVE => 'premium',
                Client::STATUS_IN_PROGRESS => 'developpement',
                default => 'standard',
            };

            $definitions['generated_' . ($index + 1)] = [
                $name,
                $city,
                $zones[$zoneKey],
                $type,
                $status,
                $this->buildClientEmail($name),
                $this->buildClientPhone($index),
                sprintf('%s, %s', $this->buildStreetLabel($index), $city),
                55 + (($index * 7) % 41),
                50 + (($index * 5) % 41),
                $segment,
                number_format(38000 + ($index * 4200), 2, '.', ''),
                'Client de demonstration pour la carte, la prospection et les tournees.',
                $commercial,
            ];
        }

        foreach ($definitions as $key => $payload) {
            [$name, $city, $zone, $type, $status, $email, $phone, $address, $potential, $solvency, $segment, $annualRevenue, $notes, $commercial] = $payload;
            $client = new Client();
            $coordinates = $this->buildFixtureCoordinates($city, $address, is_numeric($key) ? (int) $key : crc32((string) $key));
            $client
                ->setName($name)
                ->setCity($city)
                ->setZone($zone)
                ->setType($type)
                ->setStatus($status)
                ->setEmail($email)
                ->setPhone($phone)
                ->setAddress($address)
                ->setPotentialScore($potential)
                ->setSolvencyScore($solvency)
                ->setSegment($segment)
                ->setAnnualRevenue($annualRevenue)
                ->setNotes($notes)
                ->setAssignedCommercial($commercial)
                ->setLatitude(number_format($coordinates['lat'], 7, '.', ''))
                ->setLongitude(number_format($coordinates['lng'], 7, '.', ''));

            $manager->persist($client);
            $clients[$key] = $client;
        }

        return $clients;
    }

    private function buildClientEmail(string $name): string
    {
        $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name);
        $slug = preg_replace('/[^a-z0-9]+/', '.', $slug) ?? 'client';
        $slug = trim($slug, '.');

        return $slug . '@relais-demo.ma';
    }

    private function buildClientPhone(int $index): string
    {
        return sprintf('05%08d', 22000000 + ($index * 137));
    }

    private function buildStreetLabel(int $index): string
    {
        $streets = [
            'Bd Hassan II',
            'Av Mohammed V',
            'Bd Anfa',
            'Av des FAR',
            'Quartier administratif',
            'Zone clinique centrale',
        ];

        return $streets[$index % count($streets)];
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function buildFixtureCoordinates(string $city, string $address, int $index): array
    {
        $base = self::MOROCCO_CITY_COORDINATES[$city] ?? self::MOROCCO_CITY_COORDINATES['Casablanca'];
        $hash = abs(crc32($city . '|' . $address . '|' . $index));
        $latOffset = (($hash % 800) / 100000) - 0.004;
        $lngOffset = (((int) floor($hash / 1000) % 800) / 100000) - 0.004;

        return [
            'lat' => $base['lat'] + $latOffset,
            'lng' => $base['lng'] + $lngOffset,
        ];
    }

    /**
     * @param array<string, Client> $clients
     */
    private function createVisits(ObjectManager $manager, array $clients): void
    {
        $visits = [
            [$clients['clinic_alfirane'], '-1 day 10:00', 'prospection', 'haute', Visit::STATUS_COMPLETED, Visit::RESULT_QUOTE_SENT, 'Presentation IRM et accessoires.', 'Le directeur souhaite recevoir un devis detaille.', 'Envoyer l offre et relancer sous 72h.', 4],
            [$clients['pharmacie_sante'], '+1 day 11:00', 'demonstration', 'moyenne', Visit::STATUS_PLANNED, null, 'Demonstration moniteur patient.', null, 'Confirmer la disponibilite du produit.', null],
            [$clients['lab_amal'], '+2 days 09:30', 'veille', 'haute', Visit::STATUS_PENDING, Visit::RESULT_APPOINTMENT_BOOKED, 'Evaluation du besoin laboratoire.', 'Un rendez-vous technique a ete demande.', 'Partager le catalogue et bloquer une date.', 3],
        ];

        foreach ($visits as [$client, $scheduledAt, $type, $priority, $status, $result, $objective, $report, $nextAction, $interestLevel]) {
            $visit = new Visit();
            $visit
                ->setClient($client)
                ->setScheduledAt(new \DateTimeImmutable($scheduledAt))
                ->setType($type)
                ->setPriority($priority)
                ->setStatus($status)
                ->setResult($result)
                ->setObjective($objective)
                ->setReport($report)
                ->setNextAction($nextAction)
                ->setInterestLevel($interestLevel);

            if ($status === Visit::STATUS_COMPLETED) {
                $client->setLastVisitAt($visit->getScheduledAt());
            }

            $manager->persist($visit);
        }
    }

    /**
     * @param array<string, Commercial> $commercials
     */
    private function createTours(ObjectManager $manager, array $commercials): void
    {
        $definitions = [
            ['Tournee Casablanca du jour', 'Casablanca', 'tomorrow 08:00', Tour::STATUS_PROGRAMMED, 4, 0, 'Itineraire optimise secteur centre-ville.', 'Prioriser les visites a forte valeur.', $commercials['ahmed']],
            ['Tournee Rabat prospection', 'Rabat', 'next monday 08:00', Tour::STATUS_PROGRAMMED, 3, 0, 'Regroupement par secteur Agdal puis Hay Riad.', 'Verifier disponibilite produits de demo.', $commercials['salma']],
        ];

        foreach ($definitions as [$name, $city, $scheduledFor, $status, $plannedVisits, $completedVisits, $routeSummary, $notes, $commercial]) {
            $tour = new Tour();
            $tour
                ->setName($name)
                ->setCity($city)
                ->setScheduledFor(new \DateTimeImmutable($scheduledFor))
                ->setStatus($status)
                ->setPlannedVisits($plannedVisits)
                ->setCompletedVisits($completedVisits)
                ->setRouteSummary($routeSummary)
                ->setNotes($notes)
                ->setCommercial($commercial);

            $manager->persist($tour);
        }
    }

    /**
     * @param array<string, Client> $clients
     */
    private function createOffers(ObjectManager $manager, array $clients): void
    {
        $definitions = [
            ['O-2026-001', $clients['clinic_alfirane'], '85000.00', 'en_cours', '-1 day', 'Livraison sous 15 jours. Validite 30 jours.', 'Relance prevue apres demonstration finale.'],
            ['O-2026-002', $clients['pharmacie_sante'], '22000.00', 'acceptee', '-4 days', 'Paiement a 30 jours.', 'Offre acceptee apres reduction commerciale.'],
        ];

        foreach ($definitions as [$reference, $client, $amount, $status, $issuedAt, $conditions, $history]) {
            $offer = new Offer();
            $offer
                ->setReference($reference)
                ->setClient($client)
                ->setStatus($status)
                ->setIssuedAt(new \DateTimeImmutable($issuedAt))
                ->setConditionsSummary($conditions)
                ->setHistoryNotes($history);

            $productLines = $reference === 'O-2026-001'
                ? [
                    ['Echographe X', 1, '55000.00'],
                    ['Moniteur Patient', 1, '30000.00'],
                ]
                : [
                    ['Moniteur Patient', 1, '22000.00'],
                ];

            foreach ($productLines as [$productName, $quantity, $unitPrice]) {
                $product = $manager->getRepository(Product::class)->findOneBy(['name' => $productName]);
                if (!$product instanceof Product) {
                    continue;
                }

                $item = new OfferItem();
                $item
                    ->setProduct($product)
                    ->setQuantity($quantity)
                    ->setUnitPrice($unitPrice)
                    ->setLineTotal(number_format($quantity * (float) $unitPrice, 2, '.', ''));

                $offer->addItem($item);
            }

            $offer->setAmount($amount);

            $manager->persist($offer);
        }
    }

    /**
     * @return array<string, Supplier>
     */
    private function createSuppliers(ObjectManager $manager): array
    {
        $suppliers = [];

        $definitions = [
            'medtech' => ['MedTech Europe', 'France', 'valide', 92, 78, 'sales@medtech-europe.com', 'Fournisseur reactif pour l imagerie.'],
            'bioequip' => ['BioEquip Global', 'Allemagne', 'preselectionne', 80, 85, 'contact@bioequip.global', 'Bon positionnement prix sur moniteurs et accessoires.'],
        ];

        foreach ($definitions as $key => [$name, $country, $status, $reactivityScore, $priceScore, $email, $notes]) {
            $supplier = new Supplier();
            $supplier
                ->setName($name)
                ->setCountry($country)
                ->setStatus($status)
                ->setReactivityScore($reactivityScore)
                ->setPriceScore($priceScore)
                ->setContactEmail($email)
                ->setNotes($notes);

            $manager->persist($supplier);
            $suppliers[$key] = $supplier;
        }

        return $suppliers;
    }

    /**
     * @param array<string, Supplier> $suppliers
     */
    private function createProducts(ObjectManager $manager, array $suppliers): void
    {
        $definitions = [
            ['Echographe X', 'Imagerie', '55000.00', '72000.00', 6, 'standard', $suppliers['medtech']],
            ['Moniteur Patient', 'Monitoring', '12000.00', '18500.00', 14, 'standard', $suppliers['bioequip']],
            ['Sonde Cardiaque', 'Imagerie', '9000.00', '13000.00', 3, 'innovation', $suppliers['medtech']],
        ];

        foreach ($definitions as [$name, $category, $purchasePrice, $salePrice, $stockQuantity, $marketStatus, $supplier]) {
            $product = new Product();
            $product
                ->setName($name)
                ->setCategory($category)
                ->setPurchasePrice($purchasePrice)
                ->setSalePrice($salePrice)
                ->setStockQuantity($stockQuantity)
                ->setMarketStatus($marketStatus)
                ->setSupplier($supplier);

            $manager->persist($product);
        }
    }

    /**
     * @param array<string, Supplier> $suppliers
     */
    private function createSupplyOrders(ObjectManager $manager, array $suppliers): void
    {
        $definitions = [
            ['IMP-2026-001', '-5 days', 18, 'en_attente', '120000.00', $suppliers['medtech']],
            ['IMP-2026-002', '-2 days', 12, 'en_transit', '48000.00', $suppliers['bioequip']],
        ];

        foreach ($definitions as [$reference, $orderedAt, $leadTimeDays, $status, $amount, $supplier]) {
            $order = new SupplyOrder();
            $order
                ->setReference($reference)
                ->setOrderedAt(new \DateTimeImmutable($orderedAt))
                ->setLeadTimeDays($leadTimeDays)
                ->setStatus($status)
                ->setAmount($amount)
                ->setSupplier($supplier);

            $manager->persist($order);
        }
    }

    /**
     * @param array<string, Client> $clients
     */
    private function createDeliveries(ObjectManager $manager, array $clients): void
    {
        $definitions = [
            ['LIV-2026-001', '+3 days', 'planifiee', 0, 'Casablanca', $clients['clinic_alfirane']],
            ['LIV-2026-002', '+1 day', 'en_cours', 1, 'Casablanca', $clients['pharmacie_sante']],
        ];

        foreach ($definitions as [$reference, $scheduledAt, $status, $delayDays, $city, $client]) {
            $delivery = new Delivery();
            $delivery
                ->setReference($reference)
                ->setScheduledAt(new \DateTimeImmutable($scheduledAt))
                ->setStatus($status)
                ->setDelayDays($delayDays)
                ->setCity($city)
                ->setClient($client);

            $manager->persist($delivery);
        }
    }

    /**
     * @param array<string, Commercial> $commercials
     */
    private function createObjectives(ObjectManager $manager, array $commercials): void
    {
        $definitions = [
            ['Avril 2026', 500000, 35, 8, 250000, 18, 4, $commercials['ahmed']],
            ['Avril 2026', 420000, 28, 6, 180000, 14, 2, $commercials['salma']],
        ];

        foreach ($definitions as [$periodLabel, $salesTarget, $visitsTarget, $newClientsTarget, $salesActual, $visitsActual, $newClientsActual, $commercial]) {
            $objective = new Objective();
            $objective
                ->setPeriodLabel($periodLabel)
                ->setSalesTarget($salesTarget)
                ->setVisitsTarget($visitsTarget)
                ->setNewClientsTarget($newClientsTarget)
                ->setSalesActual($salesActual)
                ->setVisitsActual($visitsActual)
                ->setNewClientsActual($newClientsActual)
                ->setCommercial($commercial);

            $manager->persist($objective);
        }
    }

    private function createMarkets(ObjectManager $manager): void
    {
        $definitions = [
            ['Casablanca', 12, '450000.00', 62, 78, 73, 'saturee'],
            ['Rabat', 7, '210000.00', 70, 48, 52, 'a_developper'],
        ];

        foreach ($definitions as [$city, $clientsCount, $revenue, $competitionScore, $coverageScore, $globalScore, $zoneStatus]) {
            $market = new Market();
            $market
                ->setCity($city)
                ->setClientsCount($clientsCount)
                ->setRevenue($revenue)
                ->setCompetitionScore($competitionScore)
                ->setCoverageScore($coverageScore)
                ->setGlobalScore($globalScore)
                ->setZoneStatus($zoneStatus);

            $manager->persist($market);
        }
    }
}
