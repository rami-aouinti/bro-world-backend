<?php

declare(strict_types=1);

namespace App\User\Application\DTO\Event;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\Event as Entity;
use DateTimeInterface;
use Override;

/**
 * @package App\User\Application\DTO\Event
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Event extends RestDto
{
    protected ?string $id = null;

    protected ?string $title = null;

    protected ?string $description = null;

    protected ?DateTimeInterface $start = null;

    protected ?DateTimeInterface $end = null;

    protected ?bool $allDay = false;

    protected ?string $color = null;

    protected ?string $location = null;

    protected ?bool $isPrivate = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->setVisited('title');
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->setVisited('description');
        $this->description = $description;

        return $this;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(?DateTimeInterface $start): self
    {
        $this->setVisited('start');
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): self
    {
        $this->setVisited('end');
        $this->end = $end;

        return $this;
    }

    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    public function setAllDay(?bool $allDay): self
    {
        $this->setVisited('allDay');
        $this->allDay = $allDay;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->setVisited('color');
        $this->color = $color;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->setVisited('location');
        $this->location = $location;

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(?bool $isPrivate): self
    {
        $this->setVisited('isPrivate');
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    #[Override]
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->title = $entity->getTitle();
            $this->description = $entity->getDescription();
            $this->start = $entity->getStart();
            $this->end = $entity->getEnd();
            $this->allDay = $entity->isAllDay();
            $this->color = $entity->getColor();
            $this->location = $entity->getLocation();
            $this->isPrivate = $entity->isPrivate();
        }

        return $this;
    }
}
