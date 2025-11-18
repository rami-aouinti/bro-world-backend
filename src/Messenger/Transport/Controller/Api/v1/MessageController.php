<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\Message\Message as MessageDto;
use App\Messenger\Application\Message\MarkConversationAsRead;
use App\Messenger\Application\Service\Interfaces\ConversationMessageCacheServiceInterface;
use App\Messenger\Application\Resource\MessageResource;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Repository\Interfaces\MessageRepositoryInterface;
use App\Role\Domain\Enum\Role;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * @package App\Messenger
 *
 * @method MessageResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/messenger/message',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Messenger - Message')]
class MessageController extends Controller
{
    use Actions\Logged\CountAction;
    use Actions\Logged\FindAction;
    use Actions\Logged\FindOneAction;
    use Actions\Logged\IdsAction;
    use Actions\Logged\CreateAction;
    use Actions\Logged\PatchAction;
    use Actions\Logged\UpdateAction;
    use Actions\Logged\DeleteAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => MessageDto::class,
        Controller::METHOD_UPDATE => MessageDto::class,
        Controller::METHOD_PATCH => MessageDto::class,
    ];

    public function __construct(
        MessageResource $resource,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly ConversationMessageCacheServiceInterface $conversationMessageCacheService,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/conversation/{conversation}', methods: [Request::METHOD_GET])]
    #[IsGranted(Role::LOGGED->value)]
    public function messagesForConversation(User $loggedInUser, Request $request, Conversation $conversation): Response
    {
        if (!$conversation->getParticipants()->contains($loggedInUser)) {
            throw new AccessDeniedHttpException('You are not allowed to access this conversation');
        }

        $dataProvider = fn (): array => $this->messageRepository->findBy(['conversation' => $conversation]);

        return $this->conversationMessageCacheService->createResponse(
            $request,
            $conversation,
            $dataProvider,
            $this->getResource(),
            $this->getResponseHandler(),
        );
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/conversation/{conversation}/read', methods: [Request::METHOD_POST])]
    #[IsGranted(Role::LOGGED->value)]
    public function messagesForConversationMarkRead(User $loggedInUser, Conversation $conversation): Response
    {
        if (!$conversation->getParticipants()->contains($loggedInUser)) {
            throw new AccessDeniedHttpException('You are not allowed to access this conversation');
        }

        $this->messageBus->dispatch(new MarkConversationAsRead(
            $conversation->getId(),
            $loggedInUser->getId(),
        ));

        return new JsonResponse(['success' => true], Response::HTTP_ACCEPTED);
    }
}
