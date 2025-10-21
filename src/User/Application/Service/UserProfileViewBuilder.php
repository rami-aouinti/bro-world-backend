<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserGroup;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Domain\Repository\Interfaces\StoryRepositoryInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use JsonException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function array_filter;
use function array_key_exists;
use function array_slice;
use function array_values;
use function is_string;
use function sprintf;

/**
 * @package App\User
 */
class UserProfileViewBuilder
{
    public function __construct(
        private readonly RolesServiceInterface $rolesService,
        private readonly FollowRepositoryInterface $followRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly StoryRepositoryInterface $storyRepository,
        private readonly EventRepositoryInterface $eventRepository,
        private readonly SerializerInterface $serializer,
        private readonly CacheInterface $userCache
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function buildProfile(User $user): array
    {
        $cacheKey = 'user_profile_' . $user->getId();

        return $this->userCache->get($cacheKey, function (ItemInterface $item) use ($user): array {
            $item->expiresAfter(31536000);

            return $this->formatUser($user);
        });
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function buildProfileByUsername(string $username): array
    {
        $user = $this->userRepository->loadUserByIdentifier($username, false);

        if (!$user instanceof User) {
            throw new NotFoundHttpException(sprintf('User "%s" was not found.', $username));
        }

        return $this->buildProfile($user);
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function getStoryFeed(User $user, ?int $first = null): array
    {
        $cacheKey = 'stories_users_' . $user->getId();
        $stories = $this->userCache->get($cacheKey, function (ItemInterface $item) use ($user): array {
            $item->expiresAfter(31536000);

            return $this->storyRepository->availableStories($user);
        });

        $serialized = JSON::decode(
            $this->serializer->serialize(
                $stories,
                'json',
                [
                    'groups' => 'Story',
                ],
            ),
            true,
        );

        if ($first !== null) {
            $serialized = array_slice($serialized, 0, $first);
        }

        return $serialized;
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function getGroups(User $user): array
    {
        return JSON::decode(
            $this->serializer->serialize(
                $user->getUserGroups()->toArray(),
                'json',
                [
                    'groups' => UserGroup::SET_USER_PROFILE_GROUPS,
                ],
            ),
            true,
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function getEvents(User $user, ?int $first = null): array
    {
        $events = $this->eventRepository->findBy(
            ['user' => $user],
            ['start' => 'DESC'],
        );

        $serialized = JSON::decode(
            $this->serializer->serialize(
                $events,
                'json',
                [
                    'groups' => 'Event',
                ],
            ),
            true,
        );

        if ($first !== null) {
            $serialized = array_slice($serialized, 0, $first);
        }

        return $serialized;
    }

    public function getFollowStatuses(User $user, ?array $userIds = null): array
    {
        $statuses = $this->followRepository->getFollowStatuses($user);

        if ($userIds !== null) {
            $allowedIds = array_filter(
                $userIds,
                static fn (mixed $id): bool => is_string($id) && $id !== ''
            );
            $filtered = [];
            foreach ($allowedIds as $id) {
                if (array_key_exists($id, $statuses)) {
                    $filtered[$id] = $statuses[$id];
                }
            }
            $statuses = $filtered;
        }

        $normalized = [];
        foreach ($statuses as $userId => $status) {
            $normalized[] = [
                'user' => $userId,
                'status' => $status,
            ];
        }

        return $normalized;
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    private function formatUser(User $user): array
    {
        $document = JSON::decode(
            $this->serializer->serialize(
                $user,
                'json',
                [
                    'groups' => User::SET_USER_PROFILE,
                ],
            ),
            true,
        );

        $document['roles'] = $this->rolesService->getInheritedRoles($document['roles']);
        $document['photo'] = $document['profile']['photo'] ?? 'https://bro-world-space.com/img/person.png';
        $document['stories'] = $this->mapOwnStories($user);
        $document['friends'] = $this->buildFriendStatuses($user);

        return $document;
    }

    private function mapOwnStories(User $user): array
    {
        $stories = [];
        foreach ($user->getStories() as $story) {
            $stories[] = [
                'id' => $story->getId(),
                'mediaPath' => $story->getMediaPath(),
                'expiresAt' => $story->getExpiresAt(),
            ];
        }

        return $stories;
    }

    private function buildFriendStatuses(User $user): array
    {
        $friends = [];
        $followStatuses = $this->followRepository->getFollowStatuses($user);

        foreach ($this->userRepository->findAll() as $otherUser) {
            if (!$otherUser instanceof User || $otherUser === $user) {
                continue;
            }

            $friends[] = [
                'user' => $otherUser->getId(),
                'status' => $followStatuses[$otherUser->getId()] ?? 0,
            ];
        }

        return array_values($friends);
    }
}
