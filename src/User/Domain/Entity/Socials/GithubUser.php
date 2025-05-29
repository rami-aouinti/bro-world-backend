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
class GithubUser extends User
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'GithubUser',
        'GithubUser.githubId',

        self::SET_USER_PROFILE,
    ])]
    private ?string $githubId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'GithubUser',
        'GithubUser.avatarUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups([
        'GithubUser',
        'GithubUser.profileUrl',

        self::SET_USER_PROFILE,
    ])]
    private ?string $profileUrl = null;

    public function getGithubId(): ?string
    {
        return $this->githubId;
    }

    public function setGithubId(?string $githubId): void
    {
        $this->githubId = $githubId;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(?string $profileUrl): void
    {
        $this->profileUrl = $profileUrl;
    }
}
