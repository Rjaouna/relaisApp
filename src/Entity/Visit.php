<?php

namespace App\Entity;

use App\Repository\VisitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitRepository::class)]
class Visit
{
    public const STATUS_PLANNED = 'prevue';
    public const STATUS_COMPLETED = 'realisee';
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_CANCELLED = 'annulee';

    public const REVIEW_PENDING = 'en_attente';
    public const REVIEW_VALIDATED = 'validee';
    public const REVIEW_REJECTED = 'rejetee';

    public const RESULT_ABSENT = 'absent';
    public const RESULT_NOT_INTERESTED = 'pas_interesse';
    public const RESULT_APPOINTMENT_BOOKED = 'rdv_pris';
    public const RESULT_CLIENT_CONFIRMED = 'client_confirme';
    public const RESULT_QUOTE_SENT = 'devis_envoye';
    public const RESULT_ORDER_CONFIRMED = 'commande_confirmee';
    public const RESULT_FOLLOW_UP = 'a_relancer';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'visits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Tour $tour = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $priority = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objective = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $report = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nextAction = null;

    #[ORM\Column(nullable: true)]
    private ?int $interestLevel = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $appointmentScheduledAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $adminReviewStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminReviewComment = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $adminReviewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->type = 'prospection';
        $this->priority = 'moyenne';
        $this->status = self::STATUS_PLANNED;
    }

    public static function typeChoices(): array
    {
        return [
            'Prospection' => 'prospection',
            'Recouvrement' => 'recouvrement',
            'Demonstration produit' => 'demonstration',
            'Veille' => 'veille',
            'Contrat SAV' => 'sav',
            'Courtoisie' => 'courtoisie',
        ];
    }

    public static function priorityChoices(): array
    {
        return [
            'Haute' => 'haute',
            'Moyenne' => 'moyenne',
            'Basse' => 'basse',
        ];
    }

    public static function statusChoices(): array
    {
        return [
            'Prevue' => self::STATUS_PLANNED,
            'Realisee' => self::STATUS_COMPLETED,
            'En attente' => self::STATUS_PENDING,
            'Annulee' => self::STATUS_CANCELLED,
        ];
    }

    public static function resultChoices(): array
    {
        return [
            'Absent' => self::RESULT_ABSENT,
            'Pas interesse' => self::RESULT_NOT_INTERESTED,
            'RDV pris' => self::RESULT_APPOINTMENT_BOOKED,
            'Client confirme' => self::RESULT_CLIENT_CONFIRMED,
            'Devis envoye' => self::RESULT_QUOTE_SENT,
            'Commande confirmee' => self::RESULT_ORDER_CONFIRMED,
            'A relancer' => self::RESULT_FOLLOW_UP,
        ];
    }

    public static function reviewStatusChoices(): array
    {
        return [
            'Valider la visite' => self::REVIEW_VALIDATED,
            'Rejeter la visite' => self::REVIEW_REJECTED,
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

    public function getTour(): ?Tour
    {
        return $this->tour;
    }

    public function setTour(?Tour $tour): static
    {
        $this->tour = $tour;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): static
    {
        $this->objective = $objective;

        return $this;
    }

    public function getReport(): ?string
    {
        return $this->report;
    }

    public function setReport(?string $report): static
    {
        $this->report = $report;

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

    public function getInterestLevel(): ?int
    {
        return $this->interestLevel;
    }

    public function setInterestLevel(?int $interestLevel): static
    {
        $this->interestLevel = $interestLevel;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAppointmentScheduledAt(): ?\DateTimeImmutable
    {
        return $this->appointmentScheduledAt;
    }

    public function setAppointmentScheduledAt(?\DateTimeImmutable $appointmentScheduledAt): static
    {
        $this->appointmentScheduledAt = $appointmentScheduledAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAdminReviewStatus(): ?string
    {
        return $this->adminReviewStatus;
    }

    public function setAdminReviewStatus(?string $adminReviewStatus): static
    {
        $this->adminReviewStatus = $adminReviewStatus;

        return $this;
    }

    public function getAdminReviewComment(): ?string
    {
        return $this->adminReviewComment;
    }

    public function setAdminReviewComment(?string $adminReviewComment): static
    {
        $this->adminReviewComment = $adminReviewComment;

        return $this;
    }

    public function getAdminReviewedAt(): ?\DateTimeImmutable
    {
        return $this->adminReviewedAt;
    }

    public function setAdminReviewedAt(?\DateTimeImmutable $adminReviewedAt): static
    {
        $this->adminReviewedAt = $adminReviewedAt;

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }
}
