<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\General\Domain\Doctrine\DBAL\Types\Types as AppTypes;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\Tool\Domain\Service\Interfaces\LocalizationServiceInterface;
use App\User\Domain\Entity\Interfaces\UserGroupAwareInterface;
use App\User\Domain\Entity\Interfaces\UserInterface;
use App\User\Domain\Entity\Socials\FacebookUser;
use App\User\Domain\Entity\Socials\GithubUser;
use App\User\Domain\Entity\Socials\GoogleUser;
use App\User\Domain\Entity\Socials\InstagramUser;
use App\User\Domain\Entity\Socials\LinkedInUser;
use App\User\Domain\Entity\Traits\Blameable;
use App\User\Domain\Entity\Traits\UserRelations;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * @package App\User
 */
#[ORM\Entity]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(
    name: 'uq_username',
    columns: ['username'],
)]
#[ORM\UniqueConstraint(
    name: 'uq_email',
    columns: ['email'],
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[AssertCollection\UniqueEntity('email')]
#[AssertCollection\UniqueEntity('username')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'auth_provider', type: 'string')]
#[ORM\DiscriminatorMap([
    'local' => User::class,
    'google' => GoogleUser::class,
    'github' => GithubUser::class,
    'facebook' => FacebookUser::class,
    'instagram' => InstagramUser::class,
    'linkedIn' => LinkedInUser::class,
])]
class User implements EntityInterface, UserInterface, UserGroupAwareInterface
{
    use Blameable;
    use Timestampable;
    use UserRelations;
    use Uuid;

    final public const string SET_USER_PROFILE = 'set.UserProfile';
    final public const string SET_USER_BASIC = 'set.UserBasic';

    final public const int PASSWORD_MIN_LENGTH = 8;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.id',

        'LogLogin.user',
        'LogLoginFailure.user',
        'LogRequest.user',
        'Story',
        'UserGroup.users',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'username',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.username',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $username = '';

    #[ORM\Column(
        name: 'first_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.firstName',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $firstName = '';

    #[ORM\Column(
        name: 'last_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.lastName',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 2,
        max: 255,
    )]
    private string $lastName = '';

    #[ORM\Column(
        name: 'email',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'User',
        'User.email',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(
        name: 'language',
        type: AppTypes::ENUM_LANGUAGE,
        nullable: false,
        options: [
            'comment' => 'User language for translations',
        ],
    )]
    #[Groups([
        'User',
        'User.language',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private Language $language;

    #[ORM\Column(
        name: 'locale',
        type: AppTypes::ENUM_LOCALE,
        nullable: false,
        options: [
            'comment' => 'User locale for number, time, date, etc. formatting.',
        ],
    )]
    #[Groups([
        'User',
        'User.locale',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private Locale $locale;

    #[ORM\Column(
        name: 'timezone',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'User timezone which should be used to display time, date, etc.',
            'default' => LocalizationServiceInterface::DEFAULT_TIMEZONE,
        ],
    )]
    #[Groups([
        'User',
        'User.timezone',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $timezone = LocalizationServiceInterface::DEFAULT_TIMEZONE;

    #[ORM\Column(
        name: 'password',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Hashed password',
        ],
    )]
    private string $password = '';

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @see UserEntityEventListener
     */
    private string $plainPassword = '';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(
        name: 'enabled',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
        ],
    )]
    #[Groups([
        'User',
        'User.enabled',

        self::SET_USER_PROFILE,
        self::SET_USER_BASIC,
    ])]
    private bool $enabled = false;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
        $this->language = Language::getDefault();
        $this->locale = Locale::getDefault();
        $this->userGroups = new ArrayCollection();
        $this->logsRequest = new ArrayCollection();
        $this->logsLogin = new ArrayCollection();
        $this->logsLoginFailure = new ArrayCollection();
        $this->stories = new ArrayCollection();
        $this->followings = new ArrayCollection();
        $this->followers = new ArrayCollection();
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

    #[Override]
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Override]
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function setLocale(Locale $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(callable $encoder, string $plainPassword): self
    {
        $this->password = (string)$encoder($plainPassword);

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        if ($plainPassword !== '') {
            $this->plainPassword = $plainPassword;

            // Change some mapped values so preUpdate will get called - just blank it out
            $this->password = '';
        }

        return $this;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = '';
    }
}
