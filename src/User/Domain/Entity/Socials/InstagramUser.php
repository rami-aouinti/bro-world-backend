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
class InstagramUser extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'InstagramUser',
        'InstagramUser.instagramId',

        self::SET_USER_PROFILE,
    ])]
    private ?string $instagramId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'InstagramUser',
        'InstagramUser.avatarUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'InstagramUser',
        'InstagramUser.usernameInstagram',

        self::SET_USER_PROFILE,
    ])]
    private ?string $usernameInstagram = null;

    public function getInstagramId(): ?string
    {
        return $this->instagramId;
    }

    public function setInstagramId(?string $instagramId): void
    {
        $this->instagramId = $instagramId;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getUsernameInstagram(): ?string
    {
        return $this->usernameInstagram;
    }

    public function setUsernameInstagram(?string $usernameInstagram): void
    {
        $this->usernameInstagram = $usernameInstagram;
    }
}
