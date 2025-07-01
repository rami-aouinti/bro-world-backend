<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Service\Interfaces\UserCacheServiceInterface;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Psr\Cache\InvalidArgumentException;
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
        private UserElasticsearchServiceInterface $userElasticsearchService
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

            return $this->userRepository->findAll();
        });
    }
}
