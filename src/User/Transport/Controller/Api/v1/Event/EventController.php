<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Event;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\User\Application\DTO\Event\EventCreate;
use App\User\Application\DTO\Event\EventUpdate;
use App\User\Application\DTO\Event\EventPatch;
use App\User\Application\Resource\EventResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Event
 *
 * @method EventResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/event',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Event Management')]
class EventController extends Controller
{
    use Actions\Logged\CreateAction;
    use Actions\Logged\UpdateAction;
    use Actions\Logged\PatchAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => EventCreate::class,
        Controller::METHOD_UPDATE => EventUpdate::class,
        Controller::METHOD_PATCH => EventPatch::class,
    ];

    public function __construct(
        EventResource $resource,
    ) {
        parent::__construct($resource);
    }
}
