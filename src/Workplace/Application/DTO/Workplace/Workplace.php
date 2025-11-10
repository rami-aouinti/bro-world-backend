<?php

declare(strict_types=1);

namespace App\Workplace\Application\DTO\Workplace;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Application\Validator\Constraints as GeneralAppAssert;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;
use App\Workplace\Domain\Entity\Workplace as Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Workplace
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Workplace extends RestDto
{
    /**
     * @var array<string, string>
     */
    protected static array $mappings = [
        'plugins' => 'updatePlugins',
        'members' => 'updateMembers',
    ];

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 255)]
    protected string $name = '';

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    protected bool $isPrivate = false;

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    protected bool $enabled = true;

    #[GeneralAppAssert\EntityReferenceExists(entityClass: Plugin::class)]
    protected array $plugins = [];

    #[GeneralAppAssert\EntityReferenceExists(entityClass: User::class)]
    protected array $members = [];

    #[GeneralAppAssert\EntityReferenceExists(entityClass: User::class)]
    protected ?User $owner = null;

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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->setVisited('enabled');
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return array<int, Plugin>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @param array<int, Plugin> $plugins
     */
    public function setPlugins(array $plugins): self
    {
        $this->setVisited('plugins');
        $this->plugins = $plugins;

        return $this;
    }

    /**
     * @return array<int, User>
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * @param array<int, User> $members
     */
    public function setMembers(array $members): self
    {
        $this->setVisited('members');
        $this->members = $members;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->setVisited('owner');
        $this->owner = $owner;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    #[Override]
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->name = $entity->getName();
            $this->isPrivate = $entity->isPrivate();
            $this->enabled = $entity->isEnabled();
            $this->plugins = $entity->getPlugins()->toArray();
            $this->members = $entity->getMembers()->toArray();
            $this->owner = $entity->getOwner();
        }

        return $this;
    }

    /**
     * @param array<int, Plugin> $value
     */
    protected function updatePlugins(Entity $entity, array $value): self
    {
        $entity->setPlugins(new ArrayCollection($value));

        return $this;
    }

    /**
     * @param array<int, User> $value
     */
    protected function updateMembers(Entity $entity, array $value): self
    {
        $entity->setMembers(new ArrayCollection($value));

        return $this;
    }
}
