<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Follow
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class FollowStatusController
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     * @param User $user
     *
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/follow/status/{user}',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, User $user): JsonResponse
    {
        return new JsonResponse((bool) $this->userRepository->findOneBy([
            'follower' => $loggedInUser,
            'followed' => $user,
        ]) ?? $this->userRepository->findOneBy([
            'follower' => $user,
            'followed' => $loggedInUser,
        ]));
    }
}
