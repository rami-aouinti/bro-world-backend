<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Review;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\User\Application\DTO\Review\ReviewCreate;
use App\User\Application\DTO\Review\ReviewUpdate;
use App\User\Application\Resource\ReviewResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\Review
 *
 * @method ReviewResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/review',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Review Management')]
class ReviewController extends Controller
{
    use Actions\Logged\CreateAction;

    /**
     * @var array<string, string>
     */
    protected static array $dtoClasses = [
        Controller::METHOD_CREATE => ReviewCreate::class,
    ];

    public function __construct(
        ReviewResource $resource,
    ) {
        parent::__construct($resource);
    }
}
