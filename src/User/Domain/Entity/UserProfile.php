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
 * @package App\User
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_profile')]
class UserProfile
{
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(type: UuidBinaryOrderedTimeType::NAME, unique: true)]
    #[Groups([
        'User.profile',
        'User',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private UuidInterface $id;

    #[ORM\OneToOne(inversedBy: 'profile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'User.profile',
        'User.title',
        'User',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'Profile.description',
        'User',
        'User.description',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups([
        'User',
        'User.gender',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
        'Profile.gender'
    ])]
    private ?string $gender = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'Profile.photo','User',
        'User.photo',
        'Conversation',
        'Message',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?string $photo = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups([
        'Profile.birthday',
        'User',
        'User.birthday',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?DateTimeInterface $birthday = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'User.profile',
        'Profile.address',
        'User',
        'User.address',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups([
        'User.profile',
        'Profile.phone',
        'User',
        'User.phone',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
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

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'photo' => $this->getPhoto()
        ];
    }
}
