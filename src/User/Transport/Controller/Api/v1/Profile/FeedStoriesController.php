<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\StoryRepositoryInterface;
use Closure;
use JsonException;
use OpenApi\Attributes as OA;
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
readonly class FeedStoriesController
{
    public function __construct(
        private StoryRepositoryInterface $storyRepository,
        private CacheInterface $userCache,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     *
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/stories',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser): JsonResponse
    {
        $cacheKey = 'stories_users_' . $loggedInUser->getId();
        $stories = $this->userCache->get($cacheKey, $this->storyRepository->availableStories($loggedInUser));

        $data = JSON::decode(
            $this->serializer->serialize(
                $stories,
                'json',
                [
                    'groups' => 'Story',
                ]
            ),
            true,
        );
        return new JsonResponse($data);
    }
}
