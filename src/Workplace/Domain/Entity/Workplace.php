<?php

declare(strict_types=1);

namespace App\Workplace\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    #[Groups([
        'Workplace',
        'Workplace.owner',
    ])]
    #[Assert\NotNull]
    private User $owner;

    #[ORM\Column(
        name: 'is_private',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
        ],
    )]
    #[Groups([
        'Workplace',
        'Workplace.isPrivate',
    ])]
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private bool $isPrivate = false;

    #[ORM\Column(
        name: 'enabled',
        type: Types::BOOLEAN,
        options: [
            'default' => true,
        ],
    )]
    #[Groups([
        'Workplace',
        'Workplace.enabled',
    ])]
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private bool $enabled = true;

    /**
     * @var Collection<int, Plugin>|ArrayCollection<int, Plugin>
     */
    #[ORM\ManyToMany(targetEntity: Plugin::class)]
    #[ORM\JoinTable(name: 'workplace_plugins')]
    #[ORM\JoinColumn(name: 'workplace_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'plugin_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups([
        'Workplace',
        'Workplace.plugins',
    ])]
    private Collection|ArrayCollection $plugins;

    /**
     * @var Collection<int, User>|ArrayCollection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'workplace_members')]
    #[ORM\JoinColumn(name: 'workplace_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups([
        'Workplace',
        'Workplace.members',
    ])]
    private Collection|ArrayCollection $members;

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
        $this->plugins = new ArrayCollection();
        $this->members = new ArrayCollection();
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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        $this->addMember($owner);

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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

    /**
     * @return Collection<int, Plugin>|ArrayCollection<int, Plugin>
     */
    public function getPlugins(): Collection | ArrayCollection
    {
        return $this->plugins;
    }

    public function addPlugin(Plugin $plugin): self
    {
        if (!$this->plugins->contains($plugin)) {
            $this->plugins->add($plugin);
        }

        return $this;
    }

    public function removePlugin(Plugin $plugin): self
    {
        $this->plugins->removeElement($plugin);

        return $this;
    }

    public function setPlugins(Collection $plugins): self
    {
        $this->plugins->clear();

        foreach ($plugins as $plugin) {
            $this->addPlugin($plugin);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>|ArrayCollection<int, User>
     */
    public function getMembers(): Collection | ArrayCollection
    {
        return $this->members;
    }

    public function addMember(User $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(User $member): self
    {
        if ($member === $this->owner) {
            return $this;
        }

        $this->members->removeElement($member);

        return $this;
    }

    public function setMembers(Collection $members): self
    {
        $this->members->clear();

        foreach ($members as $member) {
            $this->addMember($member);
        }

        if (isset($this->owner)) {
            $this->addMember($this->owner);
        }

        return $this;
    }
}
