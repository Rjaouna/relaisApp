<?php

namespace App\Entity;

use App\Repository\ObjectiveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ObjectiveRepository::class)]
class Objective
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private ?string $periodLabel = null;

    #[ORM\Column]
    private int $salesTarget = 0;

    #[ORM\Column]
    private int $visitsTarget = 0;

    #[ORM\Column]
    private int $newClientsTarget = 0;

    #[ORM\Column]
    private int $salesActual = 0;

    #[ORM\Column]
    private int $visitsActual = 0;

    #[ORM\Column]
    private int $newClientsActual = 0;

    #[ORM\ManyToOne(inversedBy: 'objectives')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commercial $commercial = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodLabel(): ?string
    {
        return $this->periodLabel;
    }

    public function setPeriodLabel(string $periodLabel): static
    {
        $this->periodLabel = $periodLabel;

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

    public function getSalesActual(): int
    {
        return $this->salesActual;
    }

    public function setSalesActual(int $salesActual): static
    {
        $this->salesActual = $salesActual;

        return $this;
    }

    public function getVisitsActual(): int
    {
        return $this->visitsActual;
    }

    public function setVisitsActual(int $visitsActual): static
    {
        $this->visitsActual = $visitsActual;

        return $this;
    }

    public function getNewClientsActual(): int
    {
        return $this->newClientsActual;
    }

    public function setNewClientsActual(int $newClientsActual): static
    {
        $this->newClientsActual = $newClientsActual;

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
}
