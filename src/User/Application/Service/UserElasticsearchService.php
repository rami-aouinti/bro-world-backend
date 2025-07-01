<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Service\Interfaces\ElasticsearchServiceInterface;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\Exception\NotSupported;

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
        $document = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'enabled' => $user->isEnabled(),
            'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];

        $this->elasticsearchService->index(
            'users',
            $user->getId(),
            $document
        );
    }

    /**
     * @param User $user
     *
     * @throws NotSupported
     */
    public function updateUserInElasticsearch(User $user): void
    {
        $document = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'enabled' => $user->isEnabled(),
            'stories' => [],
            'friends' => [],
            'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];
        foreach ($user->getStories() as $key => $story) {
            $document['stories'][$key]['id'] = $story->getId();
            $document['stories'][$key]['mediaPath']  = $story->getMediaPath();
            $document['stories'][$key]['expiresAt']  = $story->getExpiresAt();
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

            $document['friends'][$key] = [
                'user' => $otherUser,
                'status' => $status,
            ];
        }
        $this->elasticsearchService->delete('users');
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->elasticsearchService->index(
                'users',
                $user->getId(),
                $document
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
}
