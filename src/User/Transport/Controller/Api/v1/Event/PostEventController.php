<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Event;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\Event;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use DateTimeImmutable;
use Exception;
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
readonly class PostEventController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EventRepositoryInterface $repository
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
     * @throws Exception
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/profile/events',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        $data = $request->request->all();
        $event = new Event(
            user: $loggedInUser,
            title: $data['title'] ?? '',
            start: new DateTimeImmutable($data['start'] ?? 'now'),
            end: isset($data['end']) ? new DateTimeImmutable($data['end']) : null,
            color: $data['color'] ?? null,
            description: $data['description'] ?? null,
            location: $data['location'] ?? null,
        );
        if($data['allDay'] ?? false) {
            $event->setAllDay((bool)$data['allDay']);
        } else {
            $event->setAllDay(false);
        }
        if($data['isPrivate'] ?? false) {
            $event->setIsPrivate((bool)$data['isPrivate']);
        } else {
            $event->setIsPrivate(false);
        }
        $this->repository->save($event);
        $output = JSON::decode(
            $this->serializer->serialize(
                $event,
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
