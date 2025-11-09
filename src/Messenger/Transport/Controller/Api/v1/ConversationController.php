<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Domain\Utils\JSON;
use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\RequestHandler;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\Conversation\Conversation as ConversationDto;
use App\Messenger\Application\Resource\ConversationResource;
use App\Role\Domain\Enum\Role;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function is_string;

/**
 * @package App\Messenger
 *
 * @method ConversationResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/messenger/conversation',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Messenger - Conversation')]
class ConversationController extends Controller
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
        Controller::METHOD_CREATE => ConversationDto::class,
        Controller::METHOD_UPDATE => ConversationDto::class,
        Controller::METHOD_PATCH => ConversationDto::class,
    ];

    public function __construct(
        ConversationResource $resource,
        private readonly UserResource $userResource,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/my', methods: [Request::METHOD_GET])]
    #[IsGranted(Role::LOGGED->value)]
    public function myConversations(User $loggedInUser, Request $request): Response
    {
        $conversations = $this->getResource()->findForUser($loggedInUser);

        return $this->getResponseHandler()->createResponse($request, $conversations, $this->getResource());
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/direct', methods: [Request::METHOD_POST])]
    #[IsGranted(Role::LOGGED->value)]
    public function createDirectConversation(User $loggedInUser, Request $request): Response
    {
        try {
            /** @var array<string, mixed> $payload */
            $payload = $request->getContent() === ''
                ? []
                : JSON::decode((string)$request->getContent(), true);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON payload.', $exception);
        }

        $receiverId = $payload['receiverId'] ?? null;

        if (!is_string($receiverId) || $receiverId === '') {
            throw new BadRequestHttpException('Field "receiverId" is required.');
        }

        if ($receiverId === $loggedInUser->getId()) {
            throw new BadRequestHttpException('You cannot start a conversation with yourself.');
        }

        $receiver = $this->userResource->findOne($receiverId, true);

        $conversationDto = (new ConversationDto())
            ->setIsGroup(false)
            ->setParticipants([
                $loggedInUser,
                $receiver,
            ]);

        $entityManagerName = RequestHandler::getTenant($request);
        $conversation = $this->getResource()->create(
            $conversationDto,
            entityManagerName: $entityManagerName,
        );

        return $this
            ->getResponseHandler()
            ->createResponse($request, $conversation, $this->getResource(), Response::HTTP_CREATED);
    }
}
