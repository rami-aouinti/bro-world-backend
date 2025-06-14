<?php

declare(strict_types=1);

namespace App\Tool\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Tool
 */
#[ORM\Entity]
#[ORM\Table(name: 'contact')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Contact implements EntityInterface
{
    use Uuid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'Contact',
        'Contact.id',
    ])]
    #[OA\Property(type: 'string', format: 'uuid')]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'email',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'Contact',
        'Contact.email'
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'Contact',
        'Contact.name'
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $name = '';

    #[ORM\Column(
        name: 'subject',
        type: Types::STRING,
        length: 255,
        nullable: true,
    )]
    #[Groups([
        'Contact',
        'Contact.subject',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $subject = '';

    #[ORM\Column(
        name: 'message',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'Contact',
        'Contact.message',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $message = '';

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
    }

    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
