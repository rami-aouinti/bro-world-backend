<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\Follow;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class FollowController
{
    public function __construct(
        private SerializerInterface $serializer,
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
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws NotSupported
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/follow/{user}',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, User $user): JsonResponse
    {
        $cacheKey = 'user_profile_' . $loggedInUser->getId();
        $cacheKeyOther = 'user_profile_' . $user->getId();
        $this->cache->deleteItem($cacheKey);
        $this->cache->deleteItem($cacheKeyOther);
        $existing = $this->followRepository->findOneBy([
            'follower' => $loggedInUser,
            'followed' => $user,
        ]);

        if ($existing) {
            $follow = new Follow($user, $loggedInUser);

            $this->followRepository->save($follow);
            /** @var array<string, string|array<string, string>> $output */
            $output = JSON::decode(
                $this->serializer->serialize(
                    $existing,
                    'json',
                    [
                        'groups' => User::SET_USER_PROFILE,
                    ]
                ),
                true,
            );

            return new JsonResponse($output);
        }

        $follow = new Follow($loggedInUser, $user);

        $this->followRepository->save($follow);

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $follow,
                'json',
                [
                    'groups' => User::SET_USER_PROFILE,
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
