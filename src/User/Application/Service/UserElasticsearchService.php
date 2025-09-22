<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Service\Interfaces\ElasticsearchServiceInterface;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use function array_values;

/**
 * @package App\User\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserElasticsearchService implements UserElasticsearchServiceInterface
{
    public function __construct(
        private ElasticsearchServiceInterface $elasticsearchService,
        private UserRepository $userRepository,
        private FollowRepositoryInterface $followRepository
    ) {
    }

    /**
     * @param User $user
     */
    public function indexUserInElasticsearch(User $user): void
    {
        $this->elasticsearchService->index(
            'users',
            $user->getId(),
            $this->buildDocument($user)
        );
    }

    /**
     * @param User $user
     */
    public function updateUserInElasticsearch(User $user): void
    {
        $this->elasticsearchService->delete('users');

        $users = $this->userRepository->findAll();

        foreach ($users as $indexedUser) {
            $this->elasticsearchService->index(
                'users',
                $indexedUser->getId(),
                $this->buildDocument($indexedUser, $users)
            );
        }
    }

    public function searchUsers(string $query): array
    {
        $response = $this->elasticsearchService->search(
            'users',
            [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $this->userRepository->getSearchColumns(),
                    ],
                ],
            ],
        );

        return array_map(static fn ($hit) => $hit['_source'], $response['hits']['hits']);
    }

    public function deleteUsers(): void
    {
        $this->elasticsearchService->delete('users');
    }

    /**
     * @param array<int, User>|null $allUsers
     *
     * @return array<string, mixed>
     */
    private function buildDocument(User $user, ?array $allUsers = null): array
    {
        $document = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'enabled' => $user->isEnabled(),
            'stories' => $this->buildStories($user),
            'friends' => [],
            'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];

        $document['friends'] = $this->buildFriends($user, $allUsers ?? $this->userRepository->findAll());

        return $document;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildStories(User $user): array
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

    /**
     * @param array<int, User> $allUsers
     *
     * @return array<int, array{id: string, status: int}>
     */
    private function buildFriends(User $user, array $allUsers): array
    {
        $friends = [];
        $friendStatuses = $this->followRepository->getFollowStatuses($user);

        foreach ($allUsers as $candidate) {
            if (!$candidate instanceof User || $candidate === $user) {
                continue;
            }

            $friendId = $candidate->getId();

            $friends[] = [
                'id' => $friendId,
                'status' => $friendStatuses[$friendId] ?? 0,
            ];
        }

        return array_values($friends);
    }
}
