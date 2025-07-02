<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Application\Service\NotificationService;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\StoryRepositoryInterface;
use Closure;
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
        private NotificationService $notificationService,
        private StoryRepositoryInterface $storyRepository
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
        $story = $this->userService->uploadStory($loggedInUser, $request);
        $this->notificationService->createNotificationStory(
            $request->headers->get('Authorization'),
            'PUSH',
            $story
        );
        $cacheKey = 'stories_users_' . $loggedInUser->getId();
        $this->userCache->delete($cacheKey);
        $this->userCache->delete('profile:stories_' . $loggedInUser->getId());
        $this->userCache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($loggedInUser)($item));

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
}
