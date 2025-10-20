<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Messenger\Domain\Entity\MessageStatus as MessageStatusEntity;
use App\Messenger\Infrastructure\Document\Repository\MessageStatusDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'messenger_message_status', repositoryClass: MessageStatusDocumentRepository::class)]
class MessageStatusDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string')]
    private string $messageId;

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'string')]
    private string $status;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public static function fromEntity(MessageStatusEntity $entity): self
    {
        $document = new self($entity->getId());

        return $document->refreshFromEntity($entity);
    }

    public function refreshFromEntity(MessageStatusEntity $entity): self
    {
        $this->messageId = $entity->getMessage()->getId();
        $this->userId = $entity->getUser()->getId();
        $this->status = $entity->getStatus()->value;

        $createdAt = $entity->getCreatedAt();
        if ($createdAt instanceof \DateTimeImmutable) {
            $this->setCreatedAt($createdAt);
        }

        $this->updatedAt = $entity->getUpdatedAt();

        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
