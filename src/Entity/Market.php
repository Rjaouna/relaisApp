<?php

namespace App\Entity;

use App\Repository\MarketRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarketRepository::class)]
class Market
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\Column]
    private int $clientsCount = 0;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $revenue = '0.00';

    #[ORM\Column]
    private int $competitionScore = 0;

    #[ORM\Column]
    private int $coverageScore = 0;

    #[ORM\Column]
    private int $globalScore = 0;

    #[ORM\Column(length: 50)]
    private string $zoneStatus = 'a_developper';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClientsCount(): int
    {
        return $this->clientsCount;
    }

    public function setClientsCount(int $clientsCount): static
    {
        $this->clientsCount = $clientsCount;

        return $this;
    }

    public function getRevenue(): string
    {
        return $this->revenue;
    }

    public function setRevenue(string $revenue): static
    {
        $this->revenue = $revenue;

        return $this;
    }

    public function getCompetitionScore(): int
    {
        return $this->competitionScore;
    }

    public function setCompetitionScore(int $competitionScore): static
    {
        $this->competitionScore = $competitionScore;

        return $this;
    }

    public function getCoverageScore(): int
    {
        return $this->coverageScore;
    }

    public function setCoverageScore(int $coverageScore): static
    {
        $this->coverageScore = $coverageScore;

        return $this;
    }

    public function getGlobalScore(): int
    {
        return $this->globalScore;
    }

    public function setGlobalScore(int $globalScore): static
    {
        $this->globalScore = $globalScore;

        return $this;
    }

    public function getZoneStatus(): string
    {
        return $this->zoneStatus;
    }

    public function setZoneStatus(string $zoneStatus): static
    {
        $this->zoneStatus = $zoneStatus;

        return $this;
    }
}
