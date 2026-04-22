<?php

namespace App\Entity;

use App\Repository\ReferenceOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferenceOptionRepository::class)]
#[ORM\Table(name: 'reference_option', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_reference_option_category_value', columns: ['category_name', 'option_value']),
])]
class ReferenceOption
{
    public const CATEGORY_CLIENT_TYPE = 'client_type';
    public const CATEGORY_CLIENT_STATUS = 'client_status';
    public const CATEGORY_CLIENT_SEGMENT = 'client_segment';
    public const CATEGORY_CLIENT_POTENTIAL_LEVEL = 'client_potential_level';
    public const CATEGORY_CLIENT_SOLVENCY_LEVEL = 'client_solvency_level';
    public const CATEGORY_VISIT_TYPE = 'visit_type';
    public const CATEGORY_VISIT_PRIORITY = 'visit_priority';
    public const CATEGORY_VISIT_STATUS = 'visit_status';
    public const CATEGORY_VISIT_RESULT = 'visit_result';
    public const CATEGORY_OFFER_STATUS = 'offer_status';
    public const CATEGORY_TOUR_STATUS = 'tour_status';
    public const CATEGORY_MARKET_ZONE_STATUS = 'market_zone_status';
    public const CATEGORY_DELIVERY_STATUS = 'delivery_status';
    public const CATEGORY_SUPPLIER_STATUS = 'supplier_status';
    public const CATEGORY_PRODUCT_CATEGORY = 'product_category';
    public const CATEGORY_PRODUCT_MARKET_STATUS = 'product_market_status';
    public const CATEGORY_SUPPLY_ORDER_STATUS = 'supply_order_status';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'category_name', length: 80)]
    private ?string $category = null;

    #[ORM\Column(length: 120)]
    private ?string $label = null;

    #[ORM\Column(name: 'option_value', length: 120)]
    private ?string $value = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function categoryChoices(): array
    {
        return [
            'Type client' => self::CATEGORY_CLIENT_TYPE,
            'Statut client' => self::CATEGORY_CLIENT_STATUS,
            'Segment client' => self::CATEGORY_CLIENT_SEGMENT,
            'Niveau potentiel client' => self::CATEGORY_CLIENT_POTENTIAL_LEVEL,
            'Niveau solvabilite client' => self::CATEGORY_CLIENT_SOLVENCY_LEVEL,
            'Type de visite' => self::CATEGORY_VISIT_TYPE,
            'Priorite de visite' => self::CATEGORY_VISIT_PRIORITY,
            'Statut de visite' => self::CATEGORY_VISIT_STATUS,
            'Resultat de visite' => self::CATEGORY_VISIT_RESULT,
            'Statut offre' => self::CATEGORY_OFFER_STATUS,
            'Statut de tournee' => self::CATEGORY_TOUR_STATUS,
            'Statut zone marche' => self::CATEGORY_MARKET_ZONE_STATUS,
            'Statut de livraison' => self::CATEGORY_DELIVERY_STATUS,
            'Statut fournisseur' => self::CATEGORY_SUPPLIER_STATUS,
            'Categorie produit' => self::CATEGORY_PRODUCT_CATEGORY,
            'Statut marche produit' => self::CATEGORY_PRODUCT_MARKET_STATUS,
            'Statut commande import' => self::CATEGORY_SUPPLY_ORDER_STATUS,
        ];
    }

    public static function categoryLabel(string $category): string
    {
        return array_search($category, self::categoryChoices(), true) ?: $category;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
