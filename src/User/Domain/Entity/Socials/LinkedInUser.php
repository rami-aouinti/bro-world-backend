<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Socials;

use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\User
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class LinkedInUser extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'LinkedInUser',
        'LinkedInUser.linkedinId',

        self::SET_USER_PROFILE,
    ])]
    private ?string $linkedinId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'LinkedInUser',
        'LinkedInUser.avatar',

        self::SET_USER_PROFILE,
    ])]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'LinkedInUser',
        'LinkedInUser.profileUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $profileUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(type: 'object', nullable: true, description: 'Raw LinkedIn payload data.')]
    #[Groups([
        'LinkedInUser',
        'LinkedInUser.raw',

        self::SET_USER_PROFILE,
    ])]
    private ?array $raw = null;

    public function getLinkedinId(): ?string
    {
        return $this->linkedinId;
    }

    public function setLinkedinId(?string $linkedinId): void
    {
        $this->linkedinId = $linkedinId;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(?string $profileUrl): void
    {
        $this->profileUrl = $profileUrl;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function setRaw(?array $raw): void
    {
        $this->raw = $raw;
    }
}
