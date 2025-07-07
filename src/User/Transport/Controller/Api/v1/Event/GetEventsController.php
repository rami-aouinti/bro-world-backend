<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Event;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
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
readonly class GetEventsController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EventRepositoryInterface $repository
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
        path: '/v1/profile/events',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser): JsonResponse
    {
        $events = $this->repository->findBy(
            ['user' => $loggedInUser],
            ['start' => 'DESC'],
        );
        $output = JSON::decode(
            $this->serializer->serialize(
                $events,
                'json',
                [
                    'groups' => 'Event',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
