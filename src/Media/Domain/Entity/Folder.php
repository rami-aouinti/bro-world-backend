<?php

declare(strict_types=1);

namespace App\Media\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Folder
 *
 * @package App\Media\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'folders')]
class Folder implements EntityInterface
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
    #[Groups(['Folder', 'Folder.id'])]
    private UuidInterface $id;

    #[ORM\Column(length: 255)]
    #[Groups(['Folder', 'Folder.name'])]
    private string $name;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?Folder $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Folder::class)]
    #[Groups(['Folder', 'Folder.children'])]
    private Collection $children;

    #[ORM\OneToMany(mappedBy: 'folder', targetEntity: File::class, cascade: ['persist', 'remove'])]
    #[Groups(['Folder', 'Folder.files'])]
    private Collection $files;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['Folder', 'Folder.isPrivate'])]
    private bool $isPrivate = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['Folder', 'Folder.isFavorite'])]
    private bool $isFavorite = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $this->children = new ArrayCollection();
        $this->files = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getParent(): ?Folder
    {
        return $this->parent;
    }

    public function setParent(?Folder $parent): void
    {
        $this->parent = $parent;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): void
    {
        $this->isPrivate = $isPrivate;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): void
    {
        $this->isFavorite = $isFavorite;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function setFiles(Collection $files): void
    {
        $this->files = $files;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}

