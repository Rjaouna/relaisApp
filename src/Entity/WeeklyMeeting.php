<?php

namespace App\Entity;

use App\Repository\WeeklyMeetingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeeklyMeetingRepository::class)]
class WeeklyMeeting
{
    public const STATUS_PLANNED = 'planifiee';
    public const STATUS_HELD = 'tenue';
    public const STATUS_CLOSED = 'cloturee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $meetingDate = null;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $teamScope = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PLANNED;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $agenda = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decisions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $actionItems = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $participantsSummary = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $organizer = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'weekly_meeting_attendee')]
    private Collection $attendees;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->meetingDate = new \DateTimeImmutable('next monday 09:00');
        $this->attendees = new ArrayCollection();
    }

    public static function statusChoices(): array
    {
        return [
            'Planifiee' => self::STATUS_PLANNED,
            'Tenue' => self::STATUS_HELD,
            'Cloturee' => self::STATUS_CLOSED,
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

    public function getMeetingDate(): ?\DateTimeImmutable
    {
        return $this->meetingDate;
    }

    public function setMeetingDate(\DateTimeImmutable $meetingDate): static
    {
        $this->meetingDate = $meetingDate;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTeamScope(): ?string
    {
        return $this->teamScope;
    }

    public function setTeamScope(?string $teamScope): static
    {
        $this->teamScope = $teamScope;

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

    public function getAgenda(): ?string
    {
        return $this->agenda;
    }

    public function setAgenda(?string $agenda): static
    {
        $this->agenda = $agenda;

        return $this;
    }

    public function getDecisions(): ?string
    {
        return $this->decisions;
    }

    public function setDecisions(?string $decisions): static
    {
        $this->decisions = $decisions;

        return $this;
    }

    public function getActionItems(): ?string
    {
        return $this->actionItems;
    }

    public function setActionItems(?string $actionItems): static
    {
        $this->actionItems = $actionItems;

        return $this;
    }

    public function getParticipantsSummary(): ?string
    {
        return $this->participantsSummary;
    }

    public function setParticipantsSummary(?string $participantsSummary): static
    {
        $this->participantsSummary = $participantsSummary;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(User $attendee): static
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees->add($attendee);
        }

        return $this;
    }

    public function removeAttendee(User $attendee): static
    {
        $this->attendees->removeElement($attendee);

        return $this;
    }

    public function syncParticipantsSummary(): void
    {
        $names = [];
        foreach ($this->attendees as $attendee) {
            $names[] = $attendee->getFullName();
        }

        $this->participantsSummary = $names === [] ? null : implode(', ', array_filter($names));
    }
}
