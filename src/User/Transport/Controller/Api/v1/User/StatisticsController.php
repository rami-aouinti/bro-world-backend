<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\User;

use App\ApiKey\Infrastructure\Repository\ApiKeyRepository;
use App\Role\Infrastructure\Repository\RoleRepository;
use App\User\Infrastructure\Repository\UserGroupRepository;
use App\User\Infrastructure\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


/**
 * Class StatisticsController
 *
 * @package App\User\Transport\Controller\Api\v1\User
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Statistics Management')]
readonly class StatisticsController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserGroupRepository $userGroupRepository,
        private RoleRepository $roleRepository,
        private ApiKeyRepository $apiKeyRepository,
        private CacheInterface $cache
    )
    {
    }


    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users
     *
     * @throws InvalidArgumentException
     * @return JsonResponse
     */
    #[Route(path: '/v1/statistics', name: 'system_statistics', methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 60)]
    public function __invoke(): JsonResponse
    {
        $cacheKey = 'system_statistics';

        $statistics = $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1h

            return [
                'usersPerMonth' => $this->userRepository->countUsersByMonth(),
                'userGroupsPerMonth' => $this->userGroupRepository->countUserGroupsByMonth(),
                'rolesPerMonth' => $this->roleRepository->countRolesByMonth(),
                'apiKeysPerMonth' => $this->apiKeyRepository->countApiKeysByMonth(),
            ];
        });

        return new JsonResponse($statistics);
    }
}
