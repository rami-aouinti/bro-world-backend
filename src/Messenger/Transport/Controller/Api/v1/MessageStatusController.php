<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\MessageStatus\MessageStatus as MessageStatusDto;
use App\Messenger\Application\Resource\MessageStatusResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Messenger
 *
 * @method MessageStatusResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/messenger/message_status',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Messenger - MessageStatus')]
class MessageStatusController extends Controller
{
    use Actions\Logged\FindAction;
    use Actions\Logged\FindOneAction;
    use Actions\Logged\CreateAction;
    use Actions\Logged\PatchAction;
    use Actions\Logged\UpdateAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => MessageStatusDto::class,
        Controller::METHOD_UPDATE => MessageStatusDto::class,
        Controller::METHOD_PATCH => MessageStatusDto::class,
    ];

    public function __construct(
        MessageStatusResource $resource,
    ) {
        parent::__construct($resource);
    }
}
