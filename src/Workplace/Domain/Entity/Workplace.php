<?php

declare(strict_types=1);

namespace App\Workplace\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * @package App\Workplace
 */
#[ORM\Entity]
#[ORM\Table(name: 'workplace')]
#[ORM\UniqueConstraint(
    name: 'uq_workplace_slug',
    columns: ['slug'],
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Workplace implements EntityInterface
{
    use Timestampable;
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'Workplace',
        'Workplace.id',
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
    )]
    #[Groups([
        'Workplace',
        'Workplace.name',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 255)]
    private string $name = '';

    #[ORM\Column(
        name: 'slug',
        type: Types::STRING,
        length: 255,
        unique: true,
    )]
    #[Gedmo\Slug(
        fields: ['name'],
        updatable: true,
        unique: true,
    )]
    #[Groups([
        'Workplace',
        'Workplace.slug',
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $slug = '';

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
