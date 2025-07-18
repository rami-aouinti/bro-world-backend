<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\User\Domain\Entity\User;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @package App\User
 */
class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $identifier;
    private readonly string $email;
    private readonly string $username;
    private readonly string $firstName;
    private readonly string $lastName;
    private readonly string $avatar;
    private readonly string $password;
    private readonly Language $language;
    private readonly Locale $locale;
    private readonly string $timezone;

    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        User $user,
        private readonly array $roles = [],
    ) {
        $this->identifier = $user->getId();
        $this->email = $user->getEmail();
        $this->username = $user->getUsername();
        $this->firstName = $user->getFirstName();
        $this->lastName = $user->getLastName();
        $this->avatar = $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/person.png';
        $this->password = $user->getPassword();
        $this->language = $user->getLanguage();
        $this->locale = $user->getLocale();
        $this->timezone = $user->getTimezone();
    }

    public function getUuid(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, string> The user roles
     */
    #[Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function eraseCredentials(): void
    {
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
