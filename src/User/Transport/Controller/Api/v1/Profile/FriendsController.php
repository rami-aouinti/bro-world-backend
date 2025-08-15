<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\Role\Application\Security\Interfaces\RolesServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use Closure;
use Doctrine\ORM\Exception\NotSupported;
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
readonly class FriendsController
{
    public function __construct(
        private SerializerInterface $serializer,
        private UserRepository $userRepository,
        private FollowRepositoryInterface $followRepository
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/profile/friends',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser): JsonResponse
    {
        $user = $this->getFormattedUser($loggedInUser);
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
        return new JsonResponse($output);
    }

    /**
     * @param User $loggedInUser
     *
     * @throws NotSupported
     * @return array
     */
    private function getFormattedUser(User $loggedInUser): array
    {
        $document = [
            'id' => $loggedInUser->getId(),
            'friends' => [],
        ];


        $allUsers = $this->userRepository->findAll();

        foreach ($allUsers as $key => $otherUser) {
            if ($otherUser === $loggedInUser) {
                continue;
            }

            $iFollowHim = $this->followRepository->findOneBy([
                'follower' => $loggedInUser,
                'followed' => $otherUser,
            ]);

            $heFollowsMe = $this->followRepository->findOneBy([
                'follower' => $otherUser,
                'followed' => $loggedInUser,
            ]);

            if ($iFollowHim && $heFollowsMe) {
                $document['friends'][$key] = $otherUser->toArray();

                foreach ($otherUser->getStories() as $otherKey => $story) {
                    $document['friends'][$key]['stories'][$otherKey]['id'] = $story->getId();
                    $document['friends'][$key]['stories'][$otherKey]['mediaPath']  = $story->getMediaPath();
                    $document['friends'][$key]['stories'][$otherKey]['expiresAt']  = $story->getExpiresAt();
                }
            }
        }

        return $document;
    }
}
