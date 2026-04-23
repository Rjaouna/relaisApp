<?php

namespace App\Entity;

use App\Repository\TourRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourRepository::class)]
class Tour
{
    public const STATUS_PROGRAMMED = 'programmee';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_COMPLETED = 'terminee';
    public const STATUS_CANCELLED = 'annulee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Zone $zone = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $scheduledFor = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PROGRAMMED;

    #[ORM\Column]
    private int $plannedVisits = 0;

    #[ORM\Column]
    private int $completedVisits = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $routeSummary = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closureRequestedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tours')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commercial $commercial = null;

    public static function statusChoices(): array
    {
        return [
            'Programmee' => self::STATUS_PROGRAMMED,
            'En cours' => self::STATUS_IN_PROGRESS,
            'Terminee' => self::STATUS_COMPLETED,
            'Annulee' => self::STATUS_CANCELLED,
        ];
    }

    public function touch(): void
    {
        // Kept for service-level consistency with other entities.
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        if ($zone?->getCity()?->getName()) {
            $this->city = $zone->getCity()?->getName();
        }

        return $this;
    }

    public function getScheduledFor(): ?\DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(\DateTimeImmutable $scheduledFor): static
    {
        $this->scheduledFor = $scheduledFor;

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

    public function getPlannedVisits(): int
    {
        return $this->plannedVisits;
    }

    public function setPlannedVisits(int $plannedVisits): static
    {
        $this->plannedVisits = $plannedVisits;

        return $this;
    }

    public function getCompletedVisits(): int
    {
        return $this->completedVisits;
    }

    public function setCompletedVisits(int $completedVisits): static
    {
        $this->completedVisits = $completedVisits;

        return $this;
    }

    public function getRouteSummary(): ?string
    {
        return $this->routeSummary;
    }

    public function setRouteSummary(?string $routeSummary): static
    {
        $this->routeSummary = $routeSummary;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getClosureRequestedAt(): ?\DateTimeImmutable
    {
        return $this->closureRequestedAt;
    }

    public function setClosureRequestedAt(?\DateTimeImmutable $closureRequestedAt): static
    {
        $this->closureRequestedAt = $closureRequestedAt;

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
