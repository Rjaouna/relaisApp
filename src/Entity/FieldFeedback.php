<?php

namespace App\Entity;

use App\Repository\FieldFeedbackRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FieldFeedbackRepository::class)]
class FieldFeedback
{
    public const CATEGORY_VEILLE = 'veille';
    public const CATEGORY_MARCHE = 'marche';
    public const CATEGORY_CONCURRENCE = 'concurrence';
    public const CATEGORY_PRODUIT = 'produit';
    public const CATEGORY_CLIENT = 'client';

    public const PRIORITY_LOW = 'basse';
    public const PRIORITY_MEDIUM = 'moyenne';
    public const PRIORITY_HIGH = 'haute';

    public const STATUS_OPEN = 'a_traiter';
    public const STATUS_ANALYSIS = 'en_analyse';
    public const STATUS_CLOSED = 'traite';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Commercial $commercial = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Visit $visit = null;

    #[ORM\Column(length: 50)]
    private string $category = self::CATEGORY_VEILLE;

    #[ORM\Column(length: 50)]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::TEXT)]
    private string $summary = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $marketSignals = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decisionAction = null;

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
            'Veille' => self::CATEGORY_VEILLE,
            'Marche' => self::CATEGORY_MARCHE,
            'Concurrence' => self::CATEGORY_CONCURRENCE,
            'Produit' => self::CATEGORY_PRODUIT,
            'Client' => self::CATEGORY_CLIENT,
        ];
    }

    public static function priorityChoices(): array
    {
        return [
            'Basse' => self::PRIORITY_LOW,
            'Moyenne' => self::PRIORITY_MEDIUM,
            'Haute' => self::PRIORITY_HIGH,
        ];
    }

    public static function statusChoices(): array
    {
        return [
            'A traiter' => self::STATUS_OPEN,
            'En analyse' => self::STATUS_ANALYSIS,
            'Traite' => self::STATUS_CLOSED,
        ];
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getCommercial(): ?Commercial
    {
        return $this->commercial;
    }

    public function setCommercial(?Commercial $commercial): static
    {
        $this->commercial = $commercial;

        return $this;
    }

    public function getVisit(): ?Visit
    {
        return $this->visit;
    }

    public function setVisit(?Visit $visit): static
    {
        $this->visit = $visit;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getMarketSignals(): ?string
    {
        return $this->marketSignals;
    }

    public function setMarketSignals(?string $marketSignals): static
    {
        $this->marketSignals = $marketSignals;

        return $this;
    }

    public function getDecisionAction(): ?string
    {
        return $this->decisionAction;
    }

    public function setDecisionAction(?string $decisionAction): static
    {
        $this->decisionAction = $decisionAction;

        return $this;
    }
}
