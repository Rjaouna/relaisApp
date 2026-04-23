<?php

namespace App\Entity;

use App\Repository\CustomerSatisfactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerSatisfactionRepository::class)]
class CustomerSatisfaction
{
    public const LEVEL_LOW = 'faible';
    public const LEVEL_MEDIUM = 'moyen';
    public const LEVEL_HIGH = 'eleve';

    public const STATUS_OPEN = 'ouvert';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_CLOSED = 'cloture';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 50)]
    private string $satisfactionLevel = self::LEVEL_MEDIUM;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $expectationSummary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $marketListening = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveryRequestedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nextAction = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function levelChoices(): array
    {
        return [
            'Faible' => self::LEVEL_LOW,
            'Moyen' => self::LEVEL_MEDIUM,
            'Eleve' => self::LEVEL_HIGH,
        ];
    }

    public static function statusChoices(): array
    {
        return [
            'Ouvert' => self::STATUS_OPEN,
            'En cours' => self::STATUS_IN_PROGRESS,
            'Cloture' => self::STATUS_CLOSED,
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

    public function getSatisfactionLevel(): string
    {
        return $this->satisfactionLevel;
    }

    public function setSatisfactionLevel(string $satisfactionLevel): static
    {
        $this->satisfactionLevel = $satisfactionLevel;

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

    public function getExpectationSummary(): ?string
    {
        return $this->expectationSummary;
    }

    public function setExpectationSummary(?string $expectationSummary): static
    {
        $this->expectationSummary = $expectationSummary;

        return $this;
    }

    public function getMarketListening(): ?string
    {
        return $this->marketListening;
    }

    public function setMarketListening(?string $marketListening): static
    {
        $this->marketListening = $marketListening;

        return $this;
    }

    public function getDeliveryRequestedAt(): ?\DateTimeImmutable
    {
        return $this->deliveryRequestedAt;
    }

    public function setDeliveryRequestedAt(?\DateTimeImmutable $deliveryRequestedAt): static
    {
        $this->deliveryRequestedAt = $deliveryRequestedAt;

        return $this;
    }

    public function getNextAction(): ?string
    {
        return $this->nextAction;
    }

    public function setNextAction(?string $nextAction): static
    {
        $this->nextAction = $nextAction;

        return $this;
    }
}
