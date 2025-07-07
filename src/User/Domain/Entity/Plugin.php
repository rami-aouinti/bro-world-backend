<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class Plugin
 *
 * @package App\Plugin\Domain\Entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'plugin')]
class Plugin implements EntityInterface
{
    use Uuid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(type: UuidBinaryOrderedTimeType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['Plugin', 'Plugin.id'])]
    private UuidInterface $id;

    #[ORM\Column(name: 'plugin_key', type: 'string', length: 50, unique: true)]
    #[Groups(['Plugin', 'Plugin.key'])]
    private string $key;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Plugin', 'Plugin.name'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['Plugin', 'Plugin.subTitle'])]
    private ?string $subTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['Plugin', 'Plugin.description'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'Plugin',
        'Plugin.logo'
    ])]
    private ?string $logo = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['Plugin', 'Plugin.icon'])]
    private string $icon;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['Plugin', 'Plugin.installed'])]
    private bool $installed;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['Plugin', 'Plugin.link'])]
    private string $link;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['Plugin', 'Plugin.pricing'])]
    private string $pricing;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['Plugin', 'Plugin.action'])]
    private string $action;

    /**
     * @throws Throwable
     */
    public function __construct(
        string $key,
        string $name,
        string $subTitle,
        string $logo,
        string $icon,
        string $link,
        string $pricing,
        string $action,
        bool $installed = false,
        ?string $description = null,
    ) {
        $this->id = $this->createUuid();
        $this->key = $key;
        $this->name = $name;
        $this->subTitle = $subTitle;
        $this->logo = $logo;
        $this->icon = $icon;
        $this->link = $link;
        $this->pricing = $pricing;
        $this->action = $action;
        $this->installed = $installed;
        $this->description = $description;
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(?string $subTitle): void
    {
        $this->subTitle = $subTitle;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getPricing(): string
    {
        return $this->pricing;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setInstalled(bool $installed): void
    {
        $this->installed = $installed;
        $this->touch();
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
