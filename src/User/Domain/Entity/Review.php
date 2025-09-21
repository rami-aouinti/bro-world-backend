<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\User\Domain\Entity\Traits\Blameable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Review
 *
 * @package App\User
 */
#[ORM\Entity]
#[ORM\Table(name: 'review')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Review implements EntityInterface
{
    use Uuid;
    use Timestampable;
    use Blameable;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups(['Review', 'Review.id'])]
    private UuidInterface $id;

    #[ORM\Column(name: 'rating', type: Types::FLOAT)]
    #[Groups(['Review', 'Review.rating'])]
    private float $rating;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    #[Groups(['Review', 'Review.comment'])]
    private ?string $comment = null;

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

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
}
