<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Entity\Traits\Uuid;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * @package App\User\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_profile')]
class UserProfile
{
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(type: UuidBinaryOrderedTimeType::NAME, unique: true)]
    private UuidInterface $id;

    #[ORM\OneToOne(inversedBy: 'profile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.title',

        User::SET_USER_PROFILE,
    ])]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.description',

        User::SET_USER_PROFILE,
    ])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.gender',

        User::SET_USER_PROFILE,
    ])]
    private ?string $gender = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.photo',

        User::SET_USER_PROFILE,
    ])]
    private ?string $photo = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.birthday',

        User::SET_USER_PROFILE,
    ])]
    private ?DateTimeInterface $birthday = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.address',

        User::SET_USER_PROFILE,
    ])]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups([
        'UserProfile',
        'UserProfile.phone',

        User::SET_USER_PROFILE,
    ])]
    private ?string $phone = null;

    /**
     * @throws Throwable
     */
    public function __construct(User $user)
    {
        $this->id = $this->createUuid();
        $this->user = $user;
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function getBirthday(): ?DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?DateTimeInterface $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }
}
