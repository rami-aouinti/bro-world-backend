<?php

declare(strict_types=1);

namespace App\Messenger\Application\DTO\Conversation;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as GeneralAppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Domain\Entity\Conversation as Entity;
use App\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

use function array_map;

/**
 * @package App\Messenger
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Conversation extends RestDto
{
    /**
     * @var array<string, string>
     */
    protected static array $mappings = [
        'participants' => 'updateParticipants',
    ];

    #[Assert\Length(max: 255)]
    protected ?string $title = null;

    protected bool $isGroup = false;

    /**
     * @var array<int, User>
     */
    #[GeneralAppAssert\EntityReferenceExists(entityClass: User::class)]
    protected array $participants = [];

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

    public function isGroup(): bool
    {
        return $this->isGroup;
    }

    public function setIsGroup(bool $isGroup): self
    {
        $this->setVisited('isGroup');
        $this->isGroup = $isGroup;

        return $this;
    }

    /**
     * @return array<int, User>
     */
    public function getParticipants(): array
    {
        return $this->participants;
    }

    /**
     * @param array<int, User> $participants
     */
    public function setParticipants(array $participants): self
    {
        $this->setVisited('participants');
        $this->participants = $participants;

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
            $this->isGroup = $entity->isGroup();
            $this->participants = array_map(
                static fn (User $participant): User => $participant,
                $entity->getParticipants()->toArray(),
            );
        }

        return $this;
    }

    /**
     * @param array<int, User> $value
     */
    protected function updateParticipants(Entity $entity, array $value): self
    {
        $entity->setParticipants(new ArrayCollection($value));

        return $this;
    }
}
