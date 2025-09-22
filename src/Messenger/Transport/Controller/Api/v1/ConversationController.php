<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\Conversation\Conversation as ConversationDto;
use App\Messenger\Application\Resource\ConversationResource;
use App\Role\Domain\Enum\Role;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

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
        private readonly Security $security,
    ) {
        parent::__construct($resource);
    }

    /**
     * @throws Throwable
     */
    #[Route(path: '/my', methods: [Request::METHOD_GET])]
    #[IsGranted(Role::LOGGED->value)]
    public function myConversations(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        $conversations = $this->getResource()->findForUser($user);

        return $this->getResponseHandler()->createResponse($request, $conversations, $this->getResource());
    }
}
