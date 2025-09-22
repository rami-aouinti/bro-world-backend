<?php

declare(strict_types=1);

namespace App\Messenger\Application\DTO\Message;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as GeneralAppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message as Entity;
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
class Message extends RestDto
{
    #[Assert\NotNull]
    #[GeneralAppAssert\EntityReferenceExists(entityClass: Conversation::class)]
    protected Conversation $conversation;

    #[Assert\NotNull]
    #[GeneralAppAssert\EntityReferenceExists(entityClass: User::class)]
    protected User $sender;

    protected ?string $text = null;

    protected ?string $mediaUrl = null;

    protected ?string $mediaType = null;

    protected ?string $attachmentUrl = null;

    protected ?string $attachmentType = null;

    #[GeneralAppAssert\EntityReferenceExists(entityClass: Entity::class)]
    protected ?Entity $replyTo = null;

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): self
    {
        $this->setVisited('conversation');
        $this->conversation = $conversation;

        return $this;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->setVisited('sender');
        $this->sender = $sender;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->setVisited('text');
        $this->text = $text;

        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl): self
    {
        $this->setVisited('mediaUrl');
        $this->mediaUrl = $mediaUrl;

        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->setVisited('mediaType');
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachmentUrl;
    }

    public function setAttachmentUrl(?string $attachmentUrl): self
    {
        $this->setVisited('attachmentUrl');
        $this->attachmentUrl = $attachmentUrl;

        return $this;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function setAttachmentType(?string $attachmentType): self
    {
        $this->setVisited('attachmentType');
        $this->attachmentType = $attachmentType;

        return $this;
    }

    public function getReplyTo(): ?Entity
    {
        return $this->replyTo;
    }

    public function setReplyTo(?Entity $replyTo): self
    {
        $this->setVisited('replyTo');
        $this->replyTo = $replyTo;

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
            $this->conversation = $entity->getConversation();
            $this->sender = $entity->getSender();
            $this->text = $entity->getText();
            $this->mediaUrl = $entity->getMediaUrl();
            $this->mediaType = $entity->getMediaType();
            $this->attachmentUrl = $entity->getAttachmentUrl();
            $this->attachmentType = $entity->getAttachmentType();
            $this->replyTo = $entity->getReplyTo();
        }

        return $this;
    }
}
