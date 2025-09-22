<?php

declare(strict_types=1);

namespace App\Messenger\Application\DTO\Reaction;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as GeneralAppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Domain\Entity\Message;
use App\Messenger\Domain\Entity\Reaction as Entity;
use App\User\Domain\Entity\User;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Messenger
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Reaction extends RestDto
{
    #[Assert\NotNull]
    #[GeneralAppAssert\EntityReferenceExists(entityClass: Message::class)]
    protected Message $message;

    #[Assert\NotNull]
    #[GeneralAppAssert\EntityReferenceExists(entityClass: User::class)]
    protected User $user;

    #[Assert\NotBlank]
    protected string $emoji = '';

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->setVisited('message');
        $this->message = $message;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->setVisited('user');
        $this->user = $user;

        return $this;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): self
    {
        $this->setVisited('emoji');
        $this->emoji = $emoji;

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
            $this->message = $entity->getMessage();
            $this->user = $entity->getUser();
            $this->emoji = $entity->getEmoji();
        }

        return $this;
    }
}
