<?php

declare(strict_types=1);

namespace App\Messenger\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\User;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Message
 *
 * @package App\Messenger\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class Message implements EntityInterface
{
    use Uuid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups(['Message', 'Message.id', 'Conversation'])]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[Groups(['Message', 'Message.conversation'])]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['Message', 'Message.sender', 'Conversation'])]
    private User $sender;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['Message', 'Message.text', 'Conversation'])]
    private ?string $text = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['Message', 'Message.mediaUrl', 'Conversation'])]
    private ?string $mediaUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['Message', 'Message.mediaType', 'Conversation'])]
    private ?string $mediaType = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['Message', 'Message.attachmentUrl', 'Conversation'])]
    private ?string $attachmentUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['Message', 'Message.attachmentType', 'Conversation'])]
    private ?string $attachmentType = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'reply_to_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['Message', 'Message.replyTo', 'Conversation'])]
    private ?self $replyTo = null;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function setSender(User $sender): void
    {
        $this->sender = $sender;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl): void
    {
        $this->mediaUrl = $mediaUrl;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachmentUrl;
    }

    public function setAttachmentUrl(?string $attachmentUrl): void
    {
        $this->attachmentUrl = $attachmentUrl;
    }

    public function getAttachmentType(): ?string
    {
        return $this->attachmentType;
    }

    public function setAttachmentType(?string $attachmentType): void
    {
        $this->attachmentType = $attachmentType;
    }

    public function getReplyTo(): ?Message
    {
        return $this->replyTo;
    }

    public function setReplyTo(?Message $replyTo): void
    {
        $this->replyTo = $replyTo;
    }
}
