<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Messenger\Domain\Entity\Message as MessageEntity;
use App\Messenger\Infrastructure\Document\Repository\MessageDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'messenger_message', repositoryClass: MessageDocumentRepository::class)]
class MessageDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string')]
    private string $conversationId;

    #[ODM\Field(type: 'string')]
    private string $senderId;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $text = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $mediaUrl = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $mediaType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $attachmentUrl = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $attachmentType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $replyToId = null;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public static function fromEntity(MessageEntity $entity): self
    {
        $document = new self($entity->getId());

        return $document->refreshFromEntity($entity);
    }

    public function refreshFromEntity(MessageEntity $entity): self
    {
        $this->conversationId = $entity->getConversation()->getId();
        $this->senderId = $entity->getSender()->getId();
        $this->text = $entity->getText();
        $this->mediaUrl = $entity->getMediaUrl();
        $this->mediaType = $entity->getMediaType();
        $this->attachmentUrl = $entity->getAttachmentUrl();
        $this->attachmentType = $entity->getAttachmentType();
        $this->replyToId = $entity->getReplyTo()?->getId();

        $createdAt = $entity->getCreatedAt();
        if ($createdAt instanceof \DateTimeImmutable) {
            $this->setCreatedAt($createdAt);
        }

        $this->updatedAt = $entity->getUpdatedAt();

        return $this;
    }

    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachmentUrl;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function getReplyToId(): ?string
    {
        return $this->replyToId;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
