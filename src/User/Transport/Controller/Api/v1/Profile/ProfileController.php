<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class ProfileController
{
    public function __construct(
        private SerializerInterface $serializer,
        private RolesServiceInterface $rolesService,
        private UserRepositoryInterface $userRepository,
        private CacheInterface $userCache
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User   $loggedInUser
     * @param string $username
     *
     * @throws InvalidArgumentException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/profile/{username}',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    #[OA\Response(
        response: 200,
        description: 'User profile data',
        content: new JsonContent(
            ref: new Model(
                type: User::class,
                groups: ['set.UserProfile'],
            ),
            type: 'object',
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid token (not found or expired)',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', description: 'Error code', type: 'integer'),
                new Property(property: 'message', description: 'Error description', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 401,
                'message' => 'JWT Token not found',
            ],
        ),
    )]
    #[OA\Response(
        response: 403,
        description: 'Access denied',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', description: 'Error code', type: 'integer'),
                new Property(property: 'message', description: 'Error description', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 403,
                'message' => 'Access denied',
            ],
        ),
    )]
    public function __invoke(User $loggedInUser, string $username): JsonResponse
    {
        $cacheKey = 'user_profile_' . $loggedInUser->getId();
        $output = $this->userCache->get($cacheKey, function (ItemInterface $item) use ($username) {
            $item->expiresAfter(3600);

            $profile = JSON::decode(
                $this->serializer->serialize(
                    $this->userRepository->loadUserByIdentifier($username, false),
                    'json',
                    [
                        'groups' => User::SET_USER_PROFILE,
                    ]
                ),
                true,
            );
            /** @var array<int, string> $roles */
            $roles = $profile['roles'];
            $profile['roles'] = $this->rolesService->getInheritedRoles($roles);
            return $profile;
        });

        return new JsonResponse($output);
    }
}
