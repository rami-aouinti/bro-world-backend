<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Socials;

use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\User
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class FacebookUser extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'FacebookUser',
        'FacebookUser.facebookId',

        self::SET_USER_PROFILE,
    ])]
    private ?string $facebookId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'FacebookUser',
        'FacebookUser.avatarUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'FacebookUser',
        'FacebookUser.profileLink',

        self::SET_USER_PROFILE,
    ])]
    private ?string $profileLink = null;

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): void
    {
        $this->facebookId = $facebookId;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getProfileLink(): ?string
    {
        return $this->profileLink;
    }

    public function setProfileLink(?string $profileLink): void
    {
        $this->profileLink = $profileLink;
    }
}
