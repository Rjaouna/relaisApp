<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsSummary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $historyNotes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, OfferItem>
     */
    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: OfferItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = 'en_cours';
        $this->issuedAt = new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getConditionsSummary(): ?string
    {
        return $this->conditionsSummary;
    }

    public function setConditionsSummary(?string $conditionsSummary): static
    {
        $this->conditionsSummary = $conditionsSummary;

        return $this;
    }

    public function getHistoryNotes(): ?string
    {
        return $this->historyNotes;
    }

    public function setHistoryNotes(?string $historyNotes): static
    {
        $this->historyNotes = $historyNotes;

        return $this;
    }

    /**
     * @return Collection<int, OfferItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OfferItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOffer($this);
        }

        return $this;
    }

    public function removeItem(OfferItem $item): static
    {
        if ($this->items->removeElement($item) && $item->getOffer() === $this) {
            $item->setOffer(null);
        }

        return $this;
    }
}
