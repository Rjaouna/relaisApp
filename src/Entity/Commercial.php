<?php

namespace App\Entity;

use App\Repository\CommercialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommercialRepository::class)]
class Commercial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $fullName = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\ManyToOne(inversedBy: 'commercials')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Zone $zone = null;

    /**
     * @var Collection<int, Zone>
     */
    #[ORM\ManyToMany(targetEntity: Zone::class, inversedBy: 'assignedCommercials')]
    #[ORM\JoinTable(name: 'commercial_zone_assignment')]
    private Collection $zones;

    #[ORM\Column]
    private int $salesTarget = 0;

    #[ORM\Column]
    private int $visitsTarget = 0;

    #[ORM\Column]
    private int $newClientsTarget = 0;

    #[ORM\Column]
    private int $currentClientsLoad = 0;

    #[ORM\Column]
    private int $currentVisitsLoad = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(inversedBy: 'commercial', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, Tour>
     */
    #[ORM\OneToMany(mappedBy: 'commercial', targetEntity: Tour::class)]
    private Collection $tours;

    /**
     * @var Collection<int, Objective>
     */
    #[ORM\OneToMany(mappedBy: 'commercial', targetEntity: Objective::class)]
    private Collection $objectives;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tours = new ArrayCollection();
        $this->objectives = new ArrayCollection();
        $this->zones = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->fullName ?? 'Commercial';
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCommercialLoad(): int
    {
        return $this->currentClientsLoad + (2 * $this->currentVisitsLoad);
    }

    public function getZoneLabel(): string
    {
        return $this->getZonesSummary();
    }

    public function getZonesSummary(): string
    {
        $labels = array_map(
            static fn (Zone $zone): string => $zone->getName() ?? 'Zone',
            $this->getZones()->toArray()
        );

        if ($labels !== []) {
            return implode(', ', $labels);
        }

        return $this->zone?->getName() ?? ($this->city ?? 'Non definie');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

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

        return $this;
    }

    public function getSalesTarget(): int
    {
        return $this->salesTarget;
    }

    public function setSalesTarget(int $salesTarget): static
    {
        $this->salesTarget = $salesTarget;

        return $this;
    }

    public function getVisitsTarget(): int
    {
        return $this->visitsTarget;
    }

    public function setVisitsTarget(int $visitsTarget): static
    {
        $this->visitsTarget = $visitsTarget;

        return $this;
    }

    public function getNewClientsTarget(): int
    {
        return $this->newClientsTarget;
    }

    public function setNewClientsTarget(int $newClientsTarget): static
    {
        $this->newClientsTarget = $newClientsTarget;

        return $this;
    }

    public function getCurrentClientsLoad(): int
    {
        return $this->currentClientsLoad;
    }

    public function setCurrentClientsLoad(int $currentClientsLoad): static
    {
        $this->currentClientsLoad = $currentClientsLoad;

        return $this;
    }

    public function getCurrentVisitsLoad(): int
    {
        return $this->currentVisitsLoad;
    }

    public function setCurrentVisitsLoad(int $currentVisitsLoad): static
    {
        $this->currentVisitsLoad = $currentVisitsLoad;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Zone>
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): static
    {
        if (!$this->zones->contains($zone)) {
            $this->zones->add($zone);
        }

        if ($this->zone === null) {
            $this->zone = $zone;
        }

        return $this;
    }

    public function removeZone(Zone $zone): static
    {
        $this->zones->removeElement($zone);

        if ($this->zone?->getId() === $zone->getId()) {
            $this->zone = $this->zones->first() ?: null;
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Tour>
     */
    public function getTours(): Collection
    {
        return $this->tours;
    }

    /**
     * @return Collection<int, Objective>
     */
    public function getObjectives(): Collection
    {
        return $this->objectives;
    }
}
