<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Application\Service\Interfaces\UserCacheServiceInterface;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use Closure;
use Doctrine\ORM\Exception\NotSupported;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @package App\User\User\Application\Service
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserCacheService implements UserCacheServiceInterface
{
    public function __construct(
        private CacheInterface $userCache,
        private UserRepositoryInterface $userRepository,
        private UserElasticsearchServiceInterface $userElasticsearchService,
        private SerializerInterface $serializer,
        private FollowRepositoryInterface $followRepository,
        private RolesServiceInterface $rolesService
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function search(string $query): array
    {
        $cacheKey = 'search_users_' . md5($query);

        return $this->userCache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->expiresAfter(31536000);

            return $this->userElasticsearchService->searchUsers($query);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clear(): void
    {
        $this->userCache->delete('users_list');

        $this->userCache->get('users_list', function (ItemInterface $item) {
            $item->expiresAfter(31536000);
            $data = JSON::decode(
                $this->serializer->serialize(
                    $this->getClosure(),
                    'json',
                    [
                        'groups' => User::SET_USER_PROFILE,
                    ]
                ),
                true,
            );
        return new JsonResponse($data);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clearProfile(?string $userId): void
    {
        $this->userCache->delete('user_profile_' . $userId);
    }

    /**
     *
     *
     * @return Closure
     */
    private function getClosure(): Closure
    {
        return function (ItemInterface $item): array {
            $item->expiresAfter(31536000);

            return $this->getFormattedUsers();
        };
    }

    /**
     *
     *
     * @param string|null $userId
     *
     * @return Closure
     */
    private function getClosureSingle(?string $userId): Closure
    {
        return function (ItemInterface $item) use ($userId): array {
            $item->expiresAfter(31536000);
            $user = $this->userRepository->find($userId);
            return $this->getFormattedUser($user);
        };
    }

    /**
     *
     * @throws NotSupported
     * @return array
     */
    private function getFormattedUsers(): array
    {
        $users = $this->userRepository->findAll();
        $document = [];
        foreach ($users as $keyUser => $user) {
            $document[$keyUser] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'enabled' => $user->isEnabled(),
                'stories' => [],
                'friends' => [],
                'profile' => [
                    'id' => $user->getProfile()?->getId(),
                    'title' => $user->getProfile()?->getTitle(),
                    'phone' => $user->getProfile()?->getPhone(),
                    'birthday' => $user->getProfile()?->getBirthday(),
                    'gender' => $user->getProfile()?->getGender(),
                    'photo' => $user->getProfile()?->getPhoto(),
                    'description' => $user->getProfile()?->getDescription(),
                    'address' => $user->getProfile()?->getAddress()
                ],
                'roles' => $user->getRoles(),
                'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
            ];
            /** @var array<int, string> $roles */
            $roles = $document[$keyUser] ['roles'];
            $document[$keyUser] ['roles'] = $this->rolesService->getInheritedRoles($roles);
            foreach ($user->getStories() as $key => $story) {
                $document[$keyUser] ['stories'][$key]['id'] = $story->getId();
                $document[$keyUser] ['stories'][$key]['mediaPath']  = $story->getMediaPath();
                $document[$keyUser] ['stories'][$key]['expiresAt']  = $story->getExpiresAt();
            }

            $allUsers = $this->userRepository->findAll();

            foreach ($allUsers as $key => $otherUser) {
                if ($otherUser === $user) {
                    continue;
                }

                $iFollowHim = $this->followRepository->findOneBy([
                    'follower' => $user,
                    'followed' => $otherUser,
                ]);

                $heFollowsMe = $this->followRepository->findOneBy([
                    'follower' => $otherUser,
                    'followed' => $user,
                ]);

                if ($iFollowHim && $heFollowsMe) {
                    $status = 1;
                } elseif ($iFollowHim && !$heFollowsMe) {
                    $status = 2;
                } elseif (!$iFollowHim && $heFollowsMe) {
                    $status = 3;
                } else {
                    $status = 0;
                }

                $document[$keyUser]['friends'][$key] = [
                    'user' => $otherUser->getId(),
                    'status' => $status,
                ];
            }
        }
        return $document;
    }

    /**
     * @param User|null $loggedInUser
     *
     * @throws NotSupported
     * @return array
     */
    private function getFormattedUser(?User $loggedInUser): array
    {
        $document = [
            'id' => $loggedInUser?->getId(),
            'firstName' => $loggedInUser?->getFirstName(),
            'lastName' => $loggedInUser?->getLastName(),
            'username' => $loggedInUser?->getUsername(),
            'email' => $loggedInUser?->getEmail(),
            'enabled' => $loggedInUser?->isEnabled(),
            'stories' => [],
            'friends' => [],
            'profile' => [
                'id' => $loggedInUser?->getProfile()?->getId(),
                'title' => $loggedInUser?->getProfile()?->getTitle(),
                'phone' => $loggedInUser?->getProfile()?->getPhone(),
                'birthday' => $loggedInUser?->getProfile()?->getBirthday(),
                'gender' => $loggedInUser?->getProfile()?->getGender(),
                'photo' => $loggedInUser?->getProfile()?->getPhoto(),
                'description' => $loggedInUser?->getProfile()?->getDescription(),
                'address' => $loggedInUser?->getProfile()?->getAddress()
            ],
            'roles' => $loggedInUser?->getRoles(),
            'photo' => $loggedInUser?->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];
        /** @var array<int, string> $roles */
        $roles = $document['roles'];
        $document['roles'] = $this->rolesService->getInheritedRoles($roles);
        foreach ($loggedInUser?->getStories() as $key => $story) {
            $document['stories'][$key]['id'] = $story->getId();
            $document['stories'][$key]['mediaPath']  = $story->getMediaPath();
            $document['stories'][$key]['expiresAt']  = $story->getExpiresAt();
        }

        $allUsers = $this->userRepository->findAll();

        foreach ($allUsers as $key => $otherUser) {
            if ($otherUser === $loggedInUser) {
                continue;
            }

            $iFollowHim = $this->followRepository->findOneBy([
                'follower' => $loggedInUser,
                'followed' => $otherUser,
            ]);

            $heFollowsMe = $this->followRepository->findOneBy([
                'follower' => $otherUser,
                'followed' => $loggedInUser,
            ]);

            if ($iFollowHim && $heFollowsMe) {
                $status = 1;
            } elseif ($iFollowHim && !$heFollowsMe) {
                $status = 2;
            } elseif (!$iFollowHim && $heFollowsMe) {
                $status = 3;
            } else {
                $status = 0;
            }

            $document['friends'][$key] = [
                'user' => $otherUser->getId(),
                'stories' => [],
                'status' => $status,
            ];

            foreach ($otherUser->getStories() as $otherKey => $story) {
                $document['friends'][$key]['stories'][$otherKey]['id'] = $story->getId();
                $document['friends'][$key]['stories'][$otherKey]['mediaPath']  = $story->getMediaPath();
                $document['friends'][$key]['stories'][$otherKey]['expiresAt']  = $story->getExpiresAt();
            }

        }

        return $document;
    }
}
