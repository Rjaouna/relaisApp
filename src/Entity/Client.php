<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    public const TYPE_CLINIC = 'clinique';
    public const TYPE_HOSPITAL = 'hopital';
    public const TYPE_PHARMACY = 'pharmacie';
    public const TYPE_LAB = 'laboratoire';

    public const STATUS_POTENTIAL = 'potentiel';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_ACTIVE = 'actif';
    public const STATUS_REFUSED = 'refuse';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(nullable: true)]
    private ?int $potentialScore = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $annualRevenue = '0.00';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastVisitAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Visit>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Visit::class)]
    private Collection $visits;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Offer::class)]
    private Collection $offers;

    public function __construct()
    {
        $this->visits = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = self::STATUS_POTENTIAL;
        $this->type = self::TYPE_CLINIC;
    }

    public static function typeChoices(): array
    {
        return [
            'Clinique' => self::TYPE_CLINIC,
            'Hopital' => self::TYPE_HOSPITAL,
            'Pharmacie' => self::TYPE_PHARMACY,
            'Laboratoire' => self::TYPE_LAB,
        ];
    }

    public static function statusChoices(): array
    {
        return [
            'Potentiel' => self::STATUS_POTENTIAL,
            'En cours' => self::STATUS_IN_PROGRESS,
            'Actif' => self::STATUS_ACTIVE,
            'Refuse' => self::STATUS_REFUSED,
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPotentialScore(): ?int
    {
        return $this->potentialScore;
    }

    public function setPotentialScore(?int $potentialScore): static
    {
        $this->potentialScore = $potentialScore;

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

    public function getAnnualRevenue(): string
    {
        return $this->annualRevenue;
    }

    public function setAnnualRevenue(string $annualRevenue): static
    {
        $this->annualRevenue = $annualRevenue;

        return $this;
    }

    public function getLastVisitAt(): ?\DateTimeImmutable
    {
        return $this->lastVisitAt;
    }

    public function setLastVisitAt(?\DateTimeImmutable $lastVisitAt): static
    {
        $this->lastVisitAt = $lastVisitAt;

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
     * @return Collection<int, Visit>
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }
}
