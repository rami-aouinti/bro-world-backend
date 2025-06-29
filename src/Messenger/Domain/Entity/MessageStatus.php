<?php

declare(strict_types=1);
namespace App\Messenger\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\User;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Throwable;


/**
 * Class MessageStatus
 *
 * @package App\Messenger\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'message_status')]
class MessageStatus implements EntityInterface
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
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column(type: 'string', enumType: MessageStatusType::class)]
    private MessageStatusType $status = MessageStatusType::DELIVERED;

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

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): void
    {
        $this->message = $message;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getStatus(): MessageStatusType
    {
        return $this->status;
    }

    public function setStatus(MessageStatusType $status): void
    {
        $this->status = $status;
    }
}
