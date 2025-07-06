<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class UnfollowController
{
    public function __construct(
        private FollowRepositoryInterface $followRepository,
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     * @param User $user
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/unfollow/{user}',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, User $user): JsonResponse
    {
        $cacheKey = 'user_profile_' . $loggedInUser->getId();
        $cacheKeyOther = 'user_profile_' . $user->getId();
        $this->cache->deleteItem($cacheKey);
        $this->cache->deleteItem($cacheKeyOther);

        $follow = $this->followRepository->findOneBy([
            'follower' => $loggedInUser,
            'followed' => $user,
        ]) ?? $this->followRepository->findOneBy([
            'follower' => $user,
            'followed' => $loggedInUser,
        ]);

        if (!$follow) {
            return new JsonResponse(['message' => 'Not following.'], 404);
        }

        $this->followRepository->remove($follow);

        return new JsonResponse(true);
    }
}
