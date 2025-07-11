<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Traits;

use App\Log\Domain\Entity\LogLogin;
use App\Log\Domain\Entity\LogLoginFailure;
use App\Log\Domain\Entity\LogRequest;
use App\User\Domain\Entity\Follow;
use App\User\Domain\Entity\Story;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserGroup;
use App\User\Domain\Entity\UserProfile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package App\User
 */
trait UserRelations
{
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserProfile::class, cascade: ['persist', 'remove'])]
    #[Groups([
        'User',
        'User.profile',

        User::SET_USER_PROFILE,
        User::SET_USER_BASIC,
    ])]
    private ?UserProfile $profile = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Story::class)]
    private Collection $stories;

    #[ORM\OneToMany(mappedBy: 'follower', targetEntity: Follow::class)]
    private Collection $followings;

    #[ORM\OneToMany(mappedBy: 'followed', targetEntity: Follow::class)]
    private Collection $followers;

    /**
     * @var Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    #[ORM\ManyToMany(
        targetEntity: UserGroup::class,
        inversedBy: 'users',
    )]
    #[ORM\JoinTable(name: 'user_has_user_group')]
    #[Groups([
        'User.userGroups',
    ])]
    protected Collection | ArrayCollection $userGroups;

    /**
     * @var Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: LogRequest::class,
    )]
    #[Groups([
        'User.logsRequest',
    ])]
    protected Collection | ArrayCollection $logsRequest;

    /**
     * @var Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: LogLogin::class,
    )]
    #[Groups([
        'User.logsLogin',
    ])]
    protected Collection | ArrayCollection $logsLogin;

    /**
     * @var Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: LogLoginFailure::class,
    )]
    #[Groups([
        'User.logsLoginFailure',
    ])]
    protected Collection | ArrayCollection $logsLoginFailure;

    /**
     * Getter for roles.
     *
     * Note that this will only return _direct_ roles that user has and
     * not the inherited ones!
     *
     * If you want to get user inherited roles you need to implement that
     * logic by yourself OR use eg. `/user/{uuid}/roles` API endpoint.
     *
     * @return array<int, string>
     */
    #[Groups([
        'User.roles',

        User::SET_USER_PROFILE,
    ])]
    public function getRoles(): array
    {
        return $this->userGroups
            ->map(static fn (UserGroup $userGroup): string => $userGroup->getRole()->getId())
            ->toArray();
    }

    /**
     * Getter for user groups collection.
     *
     * @return Collection<int, UserGroup>|ArrayCollection<int, UserGroup>
     */
    public function getUserGroups(): Collection | ArrayCollection
    {
        return $this->userGroups;
    }

    /**
     * Getter for user request log collection.
     *
     * @return Collection<int, LogRequest>|ArrayCollection<int, LogRequest>
     */
    public function getLogsRequest(): Collection | ArrayCollection
    {
        return $this->logsRequest;
    }

    /**
     * Getter for user login log collection.
     *
     * @return Collection<int, LogLogin>|ArrayCollection<int, LogLogin>
     */
    public function getLogsLogin(): Collection | ArrayCollection
    {
        return $this->logsLogin;
    }

    /**
     * Getter for user login failure log collection.
     *
     * @return Collection<int, LogLoginFailure>|ArrayCollection<int, LogLoginFailure>
     */
    public function getLogsLoginFailure(): Collection | ArrayCollection
    {
        return $this->logsLoginFailure;
    }

    /**
     * Method to attach new user group to user.
     */
    public function addUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->contains($userGroup) === false) {
            $this->userGroups->add($userGroup);
            $userGroup->addUser($this);
        }

        return $this;
    }

    /**
     * Method to remove specified user group from user.
     */
    public function removeUserGroup(UserGroup $userGroup): self
    {
        if ($this->userGroups->removeElement($userGroup)) {
            $userGroup->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Story>
     */
    public function getStories(): Collection
    {
        return $this->stories;
    }

    /**
     * @return Collection<int, Follow>
     */
    public function getFollowings(): Collection
    {
        return $this->followings;
    }

    /**
     * @return Collection<int, Follow>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function setProfile(?UserProfile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * Method to remove all many-to-many user group relations from current user.
     */
    public function clearUserGroups(): self
    {
        $this->userGroups->clear();

        return $this;
    }
}
