<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Socials;

use App\User\Domain\Entity\User;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\User
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class GithubUser extends User
{
    #[Groups(['entity:read', 'entity:write'])]
    public ?string $auth_provider = 'github';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $githubId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $login = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nodeId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([self::SET_USER_PROFILE])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $gravatarId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $htmlUrl = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $blog = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hireable = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $twitterUsername = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $publicRepos = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $publicGists = null;


    public function getAuthProvider(): ?string
    {
        return $this->auth_provider;
    }

    public function setAuthProvider(?string $auth_provider): void
    {
        $this->auth_provider = $auth_provider;
    }

    public function getGithubId(): ?string
    {
        return $this->githubId;
    }

    public function setGithubId(?string $githubId): void
    {
        $this->githubId = $githubId;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): void
    {
        $this->login = $login;
    }

    public function getNodeId(): ?string
    {
        return $this->nodeId;
    }

    public function setNodeId(?string $nodeId): void
    {
        $this->nodeId = $nodeId;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getGravatarId(): ?string
    {
        return $this->gravatarId;
    }

    public function setGravatarId(?string $gravatarId): void
    {
        $this->gravatarId = $gravatarId;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getBlog(): ?string
    {
        return $this->blog;
    }

    public function setBlog(?string $blog): void
    {
        $this->blog = $blog;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getHireable(): ?bool
    {
        return $this->hireable;
    }

    public function setHireable(?bool $hireable): void
    {
        $this->hireable = $hireable;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function getTwitterUsername(): ?string
    {
        return $this->twitterUsername;
    }

    public function setTwitterUsername(?string $twitterUsername): void
    {
        $this->twitterUsername = $twitterUsername;
    }

    public function getPublicRepos(): ?int
    {
        return $this->publicRepos;
    }

    public function setPublicRepos(?int $publicRepos): void
    {
        $this->publicRepos = $publicRepos;
    }

    public function getPublicGists(): ?int
    {
        return $this->publicGists;
    }

    public function setPublicGists(?int $publicGists): void
    {
        $this->publicGists = $publicGists;
    }
}
