<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Message\NotificationCreatedMessage;
use App\User\Domain\Exception\StoryUploadException;
use App\User\Domain\Repository\Interfaces\StoryRepositoryInterface;
use App\User\Infrastructure\Repository\FollowRepository;
use App\User\Infrastructure\Repository\UserRepository;
use Closure;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class StoryController
{
    public function __construct(
        private CacheInterface $userCache,
        private SerializerInterface $serializer,
        private UserService $userService,
        private StoryRepositoryInterface $storyRepository,
        private MessageBusInterface $bus,
        private UserRepository $userRepository,
        private FollowRepository $followRepository
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User    $loggedInUser
     * @param Request $request
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/story',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        try {
            $story = $this->userService->uploadStory($loggedInUser, $request);
        } catch (StoryUploadException $exception) {
            $statusCode = $exception->getStatusCode();
            $statusCode = $statusCode >= 400 && $statusCode < 600
                ? $statusCode
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return new JsonResponse(['error' => $exception->getMessage()], $statusCode);
        }

        $this->bus->dispatch(
            new NotificationCreatedMessage(
                $loggedInUser->getId(),
                $story->getId(),
                $request->headers->get('Authorization')
            )
        );

        $cacheKey = 'stories_users_' . $loggedInUser->getId();
        $this->userCache->delete($cacheKey);
        $this->userCache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($loggedInUser)($item));
        $this->userCache->delete('profile:stories_' . $loggedInUser->getId());

        $storiesFriends = $this->clearFriendsStory($loggedInUser);

        foreach ($storiesFriends as $friendId) {
            $cacheKey = 'stories_users_' . $friendId;
            $this->userCache->delete($cacheKey);
            $this->userCache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($this->userRepository->find($friendId))($item));
        }

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $story,
                'json',
                [
                    'groups' => 'Story',
                ]
            ),
            true,
        );

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

            return $this->storyRepository->availableStories($loggedInUser);
        };
    }

    /**
     * @param User $loggedInUser
     *
     * @throws NotSupported
     * @return array
     */
    private function clearFriendsStory(User $loggedInUser): array
    {
        $allUsers = $this->userRepository->findAll();

        $friends = [];
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
                $friends[$key] = $otherUser->getId();
            }
        }

        return $friends;
    }
}
