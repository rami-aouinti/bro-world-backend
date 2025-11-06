<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Api\v1\Workplace;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\Workplace\Application\DTO\Workplace\WorkplaceCreate;
use App\Workplace\Application\DTO\Workplace\WorkplacePatch;
use App\Workplace\Application\DTO\Workplace\WorkplaceUpdate;
use App\Workplace\Application\Resource\WorkplaceResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Workplace
 *
 * @method WorkplaceResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/workplace',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Workplace Management')]
class WorkplaceController extends Controller
{
    use Actions\Authenticated\CountAction;
    use Actions\Authenticated\FindAction;
    use Actions\Authenticated\FindOneAction;
    use Actions\Authenticated\IdsAction;
    use Actions\Authenticated\CreateAction;
    use Actions\Authenticated\DeleteAction;
    use Actions\Authenticated\PatchAction;
    use Actions\Authenticated\UpdateAction;

    /**
     * @var array<string, class-string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => WorkplaceCreate::class,
        Controller::METHOD_UPDATE => WorkplaceUpdate::class,
        Controller::METHOD_PATCH => WorkplacePatch::class,
    ];

    public function __construct(
        WorkplaceResource $resource,
    ) {
        parent::__construct($resource);
    }
}
