<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class UserPlugin
 *
 * @package App\User\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_plugin')]
class UserPlugin
{
    use Uuid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(type: UuidBinaryOrderedTimeType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['Plugin', 'Plugin.id'])]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Plugin::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['UserPlugin'])]
    private Plugin $plugin;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['UserPlugin'])]
    private bool $enabled;

    /**
     * @throws Throwable
     */
    public function __construct(User $user, Plugin $plugin, bool $enabled = true)
    {
        $this->id = $this->createUuid();
        $this->user = $user;
        $this->plugin = $plugin;
        $this->enabled = $enabled;
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function toggle(): void
    {
        $this->enabled = !$this->enabled;
    }
}
