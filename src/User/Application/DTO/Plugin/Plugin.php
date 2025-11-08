<?php

declare(strict_types=1);

namespace App\User\Application\DTO\Plugin;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\Plugin as Entity;
use Override;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @package App\Plugin
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Plugin extends RestDto
{
    #[Groups(['Plugin', 'default'])]
    protected string $key = '';

    #[Groups(['Plugin', 'default'])]
    protected string $name = '';

    #[Groups(['Plugin', 'default'])]
    protected ?string $subTitle = null;

    #[Groups(['Plugin', 'default'])]
    protected ?string $description = null;

    #[Groups(['Plugin', 'default'])]
    protected ?string $logo = null;

    #[Groups(['Plugin', 'default'])]
    protected string $icon = '';

    #[Groups(['Plugin', 'default'])]
    protected bool $installed = false;

    #[Groups(['Plugin', 'default'])]
    protected string $link = '';

    #[Groups(['Plugin', 'default'])]
    protected string $pricing = '';

    #[Groups(['Plugin', 'default'])]
    protected string $action = '';

    #[Groups(['Plugin', 'default'])]
    protected bool $active = false;

    #[Groups(['Plugin', 'default'])]
    #[Override]
    public function getId(): ?string
    {
        return parent::getId();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->setVisited('key');
        $this->key = $key;

        return $this;
    }

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

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(?string $subTitle): self
    {
        $this->setVisited('subTitle');
        $this->subTitle = $subTitle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->setVisited('description');
        $this->description = $description;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->setVisited('logo');
        $this->logo = $logo;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->setVisited('icon');
        $this->icon = $icon;

        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function setInstalled(bool $installed): self
    {
        $this->setVisited('installed');
        $this->installed = $installed;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->setVisited('link');
        $this->link = $link;

        return $this;
    }

    public function getPricing(): string
    {
        return $this->pricing;
    }

    public function setPricing(string $pricing): self
    {
        $this->setVisited('pricing');
        $this->pricing = $pricing;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->setVisited('action');
        $this->action = $action;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->setVisited('active');
        $this->active = $active;

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
            $this->id = $entity->getId();
            $this->key = $entity->getKey();
            $this->name = $entity->getName();
            $this->subTitle = $entity->getSubTitle();
            $this->description = $entity->getDescription();
            $this->logo = $entity->getLogo();
            $this->icon = $entity->getIcon();
            $this->installed = $entity->isInstalled();
            $this->link = $entity->getLink();
            $this->pricing = $entity->getPricing();
            $this->action = $entity->getAction();
        }

        return $this;
    }
}
