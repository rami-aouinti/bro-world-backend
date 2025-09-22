<?php

declare(strict_types=1);

namespace App\Messenger\Transport\Controller\Api\v1;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Messenger\Application\DTO\Reaction\Reaction as ReactionDto;
use App\Messenger\Application\Resource\ReactionResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Messenger
 *
 * @method ReactionResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/messenger/reaction',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Messenger - Reaction')]
class ReactionController extends Controller
{
    use Actions\Logged\FindAction;
    use Actions\Logged\FindOneAction;
    use Actions\Logged\CreateAction;
    use Actions\Logged\PatchAction;
    use Actions\Logged\UpdateAction;
    use Actions\Logged\DeleteAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => ReactionDto::class,
        Controller::METHOD_UPDATE => ReactionDto::class,
        Controller::METHOD_PATCH => ReactionDto::class,
    ];

    public function __construct(
        ReactionResource $resource,
    ) {
        parent::__construct($resource);
    }
}
