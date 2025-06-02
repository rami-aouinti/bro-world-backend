<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Socials;

use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\User\Domain\Entity\Socials
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class GoogleUser extends User
{
    #[Groups(['entity:read', 'entity:write'])]
    public ?string $auth_provider = 'google';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $verifiedEmail = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $givenName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $familyName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $hd = null;

    public function getAuthProvider(): ?string
    {
        return $this->auth_provider;
    }

    public function setAuthProvider(?string $auth_provider): void
    {
        $this->auth_provider = $auth_provider;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): void
    {
        $this->googleId = $googleId;
    }

    public function getVerifiedEmail(): ?bool
    {
        return $this->verifiedEmail;
    }

    public function setVerifiedEmail(?bool $verifiedEmail): void
    {
        $this->verifiedEmail = $verifiedEmail;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): void
    {
        $this->familyName = $familyName;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): void
    {
        $this->picture = $picture;
    }

    public function getHd(): ?string
    {
        return $this->hd;
    }

    public function setHd(?string $hd): void
    {
        $this->hd = $hd;
    }
}
