<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Messenger\Domain\Entity\Reaction as ReactionEntity;
use App\Messenger\Infrastructure\Document\Repository\ReactionDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'messenger_reaction', repositoryClass: ReactionDocumentRepository::class)]
class ReactionDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string')]
    private string $messageId;

    #[ODM\Field(type: 'string')]
    private string $userId;

    #[ODM\Field(type: 'string')]
    private string $emoji;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public static function fromEntity(ReactionEntity $entity): self
    {
        $document = new self($entity->getId());

        return $document->refreshFromEntity($entity);
    }

    public function refreshFromEntity(ReactionEntity $entity): self
    {
        $this->messageId = $entity->getMessage()->getId();
        $this->userId = $entity->getUser()->getId();
        $this->emoji = $entity->getEmoji();

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

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
