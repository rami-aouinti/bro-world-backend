<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Event;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\Event;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use DateTimeImmutable;
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
readonly class UpdateEventController
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
     * @param Event   $event
     * @param Request $request
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/profile/events/{event}',
        methods: [Request::METHOD_PUT],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Event $event, Request $request): JsonResponse
    {
        $event->setTitle($request->request->get('title', $event->getTitle()));
        $event->setStart(new DateTimeImmutable($request->request->get('start', $event->getStart()->format('Y-m-d H:i:s'))));
        $event->setEnd(
            $request->request->has('end')
                ? new DateTimeImmutable($request->request->get('end', $event->getEnd()?->format('Y-m-d H:i:s')))
                : null
        );
        $event->setColor($request->request->get('color', $event->getColor()));
        $event->setAllDay(
            $request->request->has('allDay')
                ? (bool)$request->request->get('allDay', $event->isAllDay())
                : $event->isAllDay()
        );
        $event->setDescription($request->request->get('description', $event->getDescription()));
        $event->setLocation($request->request->get('location', $event->getLocation()));
        $event->setIsPrivate(
            $request->request->has('isPrivate')
                ? (bool)$request->request->get('isPrivate', $event->isPrivate())
                : $event->isPrivate()
        );
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
