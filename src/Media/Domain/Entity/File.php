<?php

declare(strict_types=1);

namespace App\Media\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\Media\Domain\Enum\FileType;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class File
 *
 * @package App\Media\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'files')]
class File implements EntityInterface
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
    #[Groups(['File', 'File.id', 'Folder'])]
    private UuidInterface $id;

    #[ORM\Column(length: 255)]
    #[Groups(['File', 'File.name', 'Folder'])]
    private string $name;

    #[ORM\Column(type: 'string', enumType: FileType::class)]
    #[Groups(['File', 'File.type', 'Folder'])]
    private FileType $type;

    #[ORM\Column(length: 255)]
    #[Groups(['File', 'File.url', 'Folder'])]
    private string $url;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['File', 'File.isPrivate', 'Folder'])]
    private bool $isPrivate = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['File', 'File.isFavorite', 'Folder'])]
    private bool $isFavorite = false;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['File', 'File.extension', 'Folder'])]
    private ?string $extension = null;

    #[ORM\Column]
    #[Groups(['File', 'File.size', 'Folder'])]
    private int $size; // En octets

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'files')]
    #[Groups(['File', 'File.folder'])]
    private ?Folder $folder = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['File', 'File.user'])]
    private User $user;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): FileType
    {
        return $this->type;
    }

    public function setType(FileType $type): void
    {
        $this->type = $type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
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

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
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
