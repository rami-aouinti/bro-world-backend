<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Messenger\Domain\Entity\Conversation as ConversationEntity;
use App\Messenger\Infrastructure\Document\Repository\ConversationDocumentRepository;
use App\User\Domain\Entity\User;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use function array_map;

#[ODM\Document(collection: 'messenger_conversation', repositoryClass: ConversationDocumentRepository::class)]
class ConversationDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $title = null;

    #[ODM\Field(type: 'bool')]
    private bool $isGroup = false;

    /**
     * @var array<int, string>
     */
    #[ODM\Field(type: 'collection')]
    private array $participantIds = [];

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public static function fromEntity(ConversationEntity $entity): self
    {
        $document = new self($entity->getId());

        return $document->refreshFromEntity($entity);
    }

    public function refreshFromEntity(ConversationEntity $entity): self
    {
        $this->title = $entity->getTitle();
        $this->isGroup = $entity->isGroup();
        $this->participantIds = array_map(
            static fn (User $participant): string => $participant->getId(),
            $entity->getParticipants()->toArray(),
        );

        $createdAt = $entity->getCreatedAt();
        if ($createdAt instanceof \DateTimeImmutable) {
            $this->setCreatedAt($createdAt);
        }

        $this->updatedAt = $entity->getUpdatedAt();

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function isGroup(): bool
    {
        return $this->isGroup;
    }

    /**
     * @return array<int, string>
     */
    public function getParticipantIds(): array
    {
        return $this->participantIds;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
