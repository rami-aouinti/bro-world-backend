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
        private SerializerInterface $serializer,
        private FollowRepositoryInterface $followRepository
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
     * @throws ORMException
     * @throws OptimisticLockException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/unfollow/{user}',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, User $user): JsonResponse
    {
        $follow = $this->followRepository->findOneBy([
            'follower' => $loggedInUser,
            'followed' => $user,
        ]);

        if (!$follow) {
            return new JsonResponse(['message' => 'Not following.'], 404);
        }

        $this->followRepository->remove($follow);

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                'User unfollowed',
                'json'
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
