<?php

namespace App\Entity;

use App\Repository\ProductLaunchProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductLaunchProjectRepository::class)]
class ProductLaunchProject
{
    public const STATUS_IDEATION = 'idee';
    public const STATUS_STUDY = 'etude';
    public const STATUS_LAUNCH = 'lancement';
    public const STATUS_FOLLOW_UP = 'suivi';
    public const STATUS_CLOSED = 'cloture';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $targetCity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $targetEntities = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $marketStudy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feasibilityNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $importConditions = null;

    #[ORM\Column]
    private bool $registrationRequired = false;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_IDEATION;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $followUpNotes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function statusChoices(): array
    {
        return [
            'Idee' => self::STATUS_IDEATION,
            'Etude' => self::STATUS_STUDY,
            'Lancement' => self::STATUS_LAUNCH,
            'Suivi' => self::STATUS_FOLLOW_UP,
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTargetCity(): ?string
    {
        return $this->targetCity;
    }

    public function setTargetCity(?string $targetCity): static
    {
        $this->targetCity = $targetCity;

        return $this;
    }

    public function getTargetEntities(): ?string
    {
        return $this->targetEntities;
    }

    public function setTargetEntities(?string $targetEntities): static
    {
        $this->targetEntities = $targetEntities;

        return $this;
    }

    public function getMarketStudy(): ?string
    {
        return $this->marketStudy;
    }

    public function setMarketStudy(?string $marketStudy): static
    {
        $this->marketStudy = $marketStudy;

        return $this;
    }

    public function getFeasibilityNotes(): ?string
    {
        return $this->feasibilityNotes;
    }

    public function setFeasibilityNotes(?string $feasibilityNotes): static
    {
        $this->feasibilityNotes = $feasibilityNotes;

        return $this;
    }

    public function getImportConditions(): ?string
    {
        return $this->importConditions;
    }

    public function setImportConditions(?string $importConditions): static
    {
        $this->importConditions = $importConditions;

        return $this;
    }

    public function isRegistrationRequired(): bool
    {
        return $this->registrationRequired;
    }

    public function setRegistrationRequired(bool $registrationRequired): static
    {
        $this->registrationRequired = $registrationRequired;

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

    public function getFollowUpNotes(): ?string
    {
        return $this->followUpNotes;
    }

    public function setFollowUpNotes(?string $followUpNotes): static
    {
        $this->followUpNotes = $followUpNotes;

        return $this;
    }
}
