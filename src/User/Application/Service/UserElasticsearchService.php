<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Domain\Service\Interfaces\ElasticsearchServiceInterface;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Repository\UserRepository;

/**
 * @package App\User\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserElasticsearchService implements UserElasticsearchServiceInterface
{
    public function __construct(
        private ElasticsearchServiceInterface $elasticsearchService,
        private UserRepository $userRepository
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
            'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];

        $this->elasticsearchService->update(
            'users',
            $user->getId(),
            $document
        );
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
}
