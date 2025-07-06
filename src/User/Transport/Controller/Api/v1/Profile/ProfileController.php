<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use Closure;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use JsonException;
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
use Symfony\Component\Serializer\Exception\ExceptionInterface;
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
        private UserRepository $userRepository,
        private FollowRepositoryInterface $followRepository,
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
     * @throws NonUniqueResultException
     * @throws JsonException
     * @throws ExceptionInterface
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
        $userRepo = $this->userRepository->loadUserByIdentifier($username, false);
        $cacheKey = 'user_profile_' . $userRepo?->getId();
        $user = $this->userCache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($userRepo)($item));

        $output = JSON::decode(
            $this->serializer->serialize(
                $user,
                'json',
                [
                    'groups' => User::SET_USER_PROFILE,
                ]
            ),
            true,
        );
        /** @var array<int, string> $roles */
        $roles = $user['roles'];
        $output['roles'] = $this->rolesService->getInheritedRoles($roles);

        return new JsonResponse($output);
    }

    /**
     *
     * @param User $loggedInUser
     *
     * @return Closure
     */
    private function getClosure(User $loggedInUser): Closure
    {
        return function (ItemInterface $item) use ($loggedInUser): array {
            $item->expiresAfter(31536000);

            return $this->getFormattedUser($loggedInUser);
        };
    }

    /**
     * @param User $user
     *
     * @throws NotSupported
     * @return array
     */
    private function getFormattedUser(User $user): array
    {
        $document = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'enabled' => $user->isEnabled(),
            'stories' => [],
            'roles' => $user->getRoles(),
            'friends' => [],
            'profile' => [
                'id' => $user->getProfile()?->getId(),
                'title' => $user->getProfile()?->getTitle(),
                'phone' => $user->getProfile()?->getPhone(),
                'birthday' => $user->getProfile()?->getBirthday(),
                'gender' => $user->getProfile()?->getGender(),
                'photo' => $user->getProfile()?->getPhoto(),
                'description' => $user->getProfile()?->getDescription(),
                'address' => $user->getProfile()?->getAddress()
            ],
            'photo' => $user->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png',
        ];
        /** @var array<int, string> $roles */
        $roles = $document['roles'];
        $document['roles'] = $this->rolesService->getInheritedRoles($roles);
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
                'user' => $otherUser->getId(),
                'status' => $status,
            ];
        }

        return $document;
    }
}
