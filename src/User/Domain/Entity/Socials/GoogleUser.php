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
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'GoogleUser',
        'GoogleUser.googleId',

        self::SET_USER_PROFILE,
    ])]
    private ?string $googleId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'GoogleUser',
        'GoogleUser.avatarUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $avatarUrl = null;

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }
}
