<?php

declare(strict_types=1);

namespace App\Media\Application\DTO\Folder;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as AppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Media\Domain\Entity\Folder as Entity;
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
class Folder extends RestDto
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(max: 255)]
    protected string $name = '';

    #[AppAssert\EntityReferenceExists(entityClass: Entity::class)]
    protected ?Entity $parent = null;

    protected bool $isPrivate = false;

    protected bool $isFavorite = false;

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

    public function getParent(): ?Entity
    {
        return $this->parent;
    }

    public function setParent(?Entity $parent): self
    {
        $this->setVisited('parent');
        $this->parent = $parent;

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
            $this->parent = $entity->getParent();
            $this->isPrivate = $entity->isPrivate();
            $this->isFavorite = $entity->isFavorite();
            $this->user = $entity->getUser();
        }

        return $this;
    }
}
