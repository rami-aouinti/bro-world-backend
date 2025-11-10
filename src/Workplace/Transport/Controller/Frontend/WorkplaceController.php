<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\General\Transport\Rest\Controller;
use App\General\Transport\Rest\ResponseHandler;
use App\General\Transport\Rest\Traits\Actions;
use App\User\Domain\Entity\User;
use App\Workplace\Application\DTO\Workplace\WorkplaceCreate;
use App\Workplace\Application\DTO\Workplace\WorkplacePatch;
use App\Workplace\Application\DTO\Workplace\WorkplaceUpdate;
use App\Workplace\Application\Resource\WorkplaceResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @package App\Workplace
 *
 * @method WorkplaceResource getResource()
 * @method ResponseHandler getResponseHandler()
 */
#[AsController]
#[Route(
    path: '/v1/frontend/workplaces',
)]
#[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
#[OA\Tag(name: 'Workplace Management')]
class WorkplaceController
{

    /**
     * @throws Throwable
     */
    #[Route(path: '/', methods: [Request::METHOD_GET])]
    public function myWorkplaces(User $loggedInUser, Request $request): Response
    {
        $workplaces = $this->getResource()->findForMember($loggedInUser);

        return $this->getResponseHandler()->createResponse($request, $workplaces, $this->getResource());
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/{slug}',
        requirements: [
            'slug' => Requirement::ASCII_SLUG,
        ],
        methods: [Request::METHOD_GET],
    )]
    public function getWorkplaceBySlug(User $loggedInUser, Request $request, string $slug): Response
    {
        $workplace = $this->getResource()->findOneForMemberBySlug($loggedInUser, $slug);

        return $this->getResponseHandler()->createResponse($request, $workplace, $this->getResource());
    }
}
