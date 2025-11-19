<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\User\Domain\Entity\Traits\Blameable;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Event
 *
 * @package App\User
 */
#[ORM\Entity]
#[ORM\Table(name: 'calendar_event')]
class Event implements EntityInterface
{
    use Uuid;
    use Timestampable;
    use Blameable;

    #[ORM\Id]
    #[ORM\Column(type: UuidBinaryOrderedTimeType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['Event', 'Event.id'])]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['Event', 'Event.user'])]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Event', 'Event.title'])]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['Event', 'Event.description'])]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['Event', 'Event.start'])]
    private DateTimeInterface $start;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['Event', 'Event.end'])]
    private ?DateTimeInterface $end = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
    #[Groups(['Event', 'Event.allDay'])]
    private bool $allDay;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['Event', 'Event.color'])]
    private ?string $color = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['Event', 'Event.location'])]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['Event', 'Event.isPrivate'])]
    private bool $isPrivate;

    #[ORM\Column(name: 'four_hour_reminder_sent', type: 'boolean', options: ['default' => false])]
    private bool $fourHourReminderSent = false;

    #[ORM\Column(name: 'fifteen_minute_reminder_sent', type: 'boolean', options: ['default' => false])]
    private bool $fifteenMinuteReminderSent = false;

    /**
     * @throws Throwable
     */
    public function __construct(
        User $user,
        string $title,
        DateTimeInterface $start,
        ?DateTimeInterface $end = null,
        ?string $color = null,
        ?string $description = null,
        ?string $location = null,
        bool $allDay = false,
        bool $isPrivate = false,
    ) {
        $this->id = $this->createUuid();
        $this->user = $user;
        $this->title = $title;
        $this->start = $start;
        $this->end = $end ?? $start;
        $this->color = $color;
        $this->description = $description;
        $this->location = $location;
        $this->allDay = $allDay;
        $this->isPrivate = $isPrivate;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function setStart(DateTimeInterface $start): void
    {
        $this->start = $start;
        $this->touch();
    }

    public function setEnd(?DateTimeInterface $end): void
    {
        $this->end = $end;
        $this->touch();
    }

    public function setAllDay(bool $allDay): void
    {
        $this->allDay = $allDay;
        $this->touch();
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
        $this->touch();
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
        $this->touch();
    }

    public function setIsPrivate(bool $isPrivate): void
    {
        $this->isPrivate = $isPrivate;
        $this->touch();
    }

    public function isFourHourReminderSent(): bool
    {
        return $this->fourHourReminderSent;
    }

    public function markFourHourReminderSent(): void
    {
        $this->fourHourReminderSent = true;
        $this->touch();
    }

    public function isFifteenMinuteReminderSent(): bool
    {
        return $this->fifteenMinuteReminderSent;
    }

    public function markFifteenMinuteReminderSent(): void
    {
        $this->fifteenMinuteReminderSent = true;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
