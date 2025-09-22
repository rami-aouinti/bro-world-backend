<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\Message\Message as MessageDto;
use App\Messenger\Application\Resource\ConversationResource;
use App\Messenger\Application\Resource\MessageResource;
use App\Messenger\Domain\Entity\Conversation;
use App\Role\Domain\Enum\Role;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        private readonly ConversationResource $conversationResource,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/conversation/{conversationId}', methods: [Request::METHOD_GET])]
    #[IsGranted(Role::LOGGED->value)]
    public function messagesForConversation(Request $request, string $conversationId): Response
    {
        $conversation = $this->conversationResource->getReference($conversationId);

        if (!$conversation instanceof Conversation) {
            throw new NotFoundHttpException('Conversation not found');
        }

        $messages = $this->getResource()->find(
            criteria: ['conversation' => $conversation],
            orderBy: ['createdAt' => 'ASC'],
        );

        return $this->getResponseHandler()->createResponse($request, $messages, $this->getResource());
    }
}
