<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Profile;

use App\General\Domain\Utils\JSON;
use App\User\Application\Resource\UserResource;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
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
use Throwable;

/**
 * @package App\User
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class AvatarController
{
    public function __construct(
        private SerializerInterface $serializer,
        private UserService $userService,
        private UserResource $userResource,
        private EntityManagerInterface $entityManager
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
        path: '/v1/avatar',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        $avatar = $this->userService->uploadPhoto($loggedInUser, $request);

        $profile = $loggedInUser->getProfile();
        if (!$profile) {
            $profile = new UserProfile($loggedInUser);

        }
        $profile->setPhoto($avatar);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        $this->userResource->save($loggedInUser);
        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $profile,
                'json',
                [
                    'groups' => 'Profile',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
