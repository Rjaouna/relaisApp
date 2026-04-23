<?php

namespace App\Entity;

use App\Repository\SupplierConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupplierConsultationRepository::class)]
class SupplierConsultation
{
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_SENT = 'envoyee';
    public const STATUS_QUOTE_RECEIVED = 'devis_recu';
    public const STATUS_NEGOTIATION = 'negociation';
    public const STATUS_VALIDATED = 'validee';
    public const STATUS_REJECTED = 'rejetee';

    public const SAMPLE_PENDING = 'en_attente';
    public const SAMPLE_RECEIVED = 'recu';
    public const SAMPLE_COMPLIANT = 'conforme';
    public const SAMPLE_REJECTED = 'non_conforme';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\Column(length: 180)]
    private string $needTitle = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $needDetails = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $expectedDelay = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 50)]
    private string $sampleStatus = self::SAMPLE_PENDING;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $quotedAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $negotiatedAmount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $complianceNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $negotiationNotes = null;

    #[ORM\Column]
    private bool $selectedSupplier = false;

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
            'Brouillon' => self::STATUS_DRAFT,
            'Consultation envoyee' => self::STATUS_SENT,
            'Devis recu' => self::STATUS_QUOTE_RECEIVED,
            'Negociation' => self::STATUS_NEGOTIATION,
            'Validee' => self::STATUS_VALIDATED,
            'Rejetee' => self::STATUS_REJECTED,
        ];
    }

    public static function sampleStatusChoices(): array
    {
        return [
            'En attente' => self::SAMPLE_PENDING,
            'Echantillon recu' => self::SAMPLE_RECEIVED,
            'Conforme' => self::SAMPLE_COMPLIANT,
            'Non conforme' => self::SAMPLE_REJECTED,
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getNeedTitle(): string
    {
        return $this->needTitle;
    }

    public function setNeedTitle(string $needTitle): static
    {
        $this->needTitle = $needTitle;

        return $this;
    }

    public function getNeedDetails(): string
    {
        return $this->needDetails;
    }

    public function setNeedDetails(string $needDetails): static
    {
        $this->needDetails = $needDetails;

        return $this;
    }

    public function getExpectedDelay(): ?string
    {
        return $this->expectedDelay;
    }

    public function setExpectedDelay(?string $expectedDelay): static
    {
        $this->expectedDelay = $expectedDelay;

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

    public function getSampleStatus(): string
    {
        return $this->sampleStatus;
    }

    public function setSampleStatus(string $sampleStatus): static
    {
        $this->sampleStatus = $sampleStatus;

        return $this;
    }

    public function getQuotedAmount(): ?string
    {
        return $this->quotedAmount;
    }

    public function setQuotedAmount(?string $quotedAmount): static
    {
        $this->quotedAmount = $quotedAmount;

        return $this;
    }

    public function getNegotiatedAmount(): ?string
    {
        return $this->negotiatedAmount;
    }

    public function setNegotiatedAmount(?string $negotiatedAmount): static
    {
        $this->negotiatedAmount = $negotiatedAmount;

        return $this;
    }

    public function getComplianceNotes(): ?string
    {
        return $this->complianceNotes;
    }

    public function setComplianceNotes(?string $complianceNotes): static
    {
        $this->complianceNotes = $complianceNotes;

        return $this;
    }

    public function getNegotiationNotes(): ?string
    {
        return $this->negotiationNotes;
    }

    public function setNegotiationNotes(?string $negotiationNotes): static
    {
        $this->negotiationNotes = $negotiationNotes;

        return $this;
    }

    public function isSelectedSupplier(): bool
    {
        return $this->selectedSupplier;
    }

    public function setSelectedSupplier(bool $selectedSupplier): static
    {
        $this->selectedSupplier = $selectedSupplier;

        return $this;
    }
}
