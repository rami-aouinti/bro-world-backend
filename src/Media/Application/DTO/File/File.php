<?php

declare(strict_types=1);

namespace App\Media\Application\DTO\File;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as AppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Media\Domain\Entity\File as Entity;
use App\Media\Domain\Entity\Folder as FolderEntity;
use App\Media\Domain\Enum\FileType;
use App\User\Domain\Entity\User;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Media
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class File extends RestDto
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(max: 255)]
    protected string $name = '';

    #[Assert\NotNull]
    protected FileType $type = FileType::OTHER;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(max: 255)]
    protected string $url = '';

    protected bool $isPrivate = false;

    protected bool $isFavorite = false;

    #[Assert\Length(max: 10)]
    protected ?string $extension = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    protected int $size = 0;

    #[AppAssert\EntityReferenceExists(entityClass: FolderEntity::class)]
    protected ?FolderEntity $folder = null;

    protected ?User $user = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->setVisited('name');
        $this->name = $name;

        return $this;
    }

    public function getType(): FileType
    {
        return $this->type;
    }

    public function setType(FileType $type): self
    {
        $this->setVisited('type');
        $this->type = $type;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->setVisited('url');
        $this->url = $url;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->setVisited('isPrivate');
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): self
    {
        $this->setVisited('isFavorite');
        $this->isFavorite = $isFavorite;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->setVisited('extension');
        $this->extension = $extension;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->setVisited('size');
        $this->size = $size;

        return $this;
    }

    public function getFolder(): ?FolderEntity
    {
        return $this->folder;
    }

    public function setFolder(?FolderEntity $folder): self
    {
        $this->setVisited('folder');
        $this->folder = $folder;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->setVisited('user');
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->name = $entity->getName();
            $this->type = $entity->getType();
            $this->url = $entity->getUrl();
            $this->isPrivate = $entity->isPrivate();
            $this->isFavorite = $entity->isFavorite();
            $this->extension = $entity->getExtension();
            $this->size = $entity->getSize();
            $this->folder = $entity->getFolder();
            $this->user = $entity->getUser();
        }

        return $this;
    }
}
