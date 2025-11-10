<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\User\Domain\Entity\User;
use App\Workplace\Application\Resource\WorkplaceResource;
use App\Workplace\Domain\Entity\Workplace;
use App\Workplace\Transport\Controller\Frontend\Traits\WorkplaceOwnershipTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Class DeleteWorkplaceController
 */
#[AsController]
#[OA\Tag(name: 'Workplace Frontend')]
readonly class DeleteWorkplaceController
{
    use WorkplaceOwnershipTrait;

    public function __construct(
        private WorkplaceResource $workplaceResource,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route(
        path: '/v1/frontend/workplaces/{workplace}',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Workplace $workplace): Response
    {
        $this->assertOwnership($workplace, $loggedInUser);
        $this->workplaceResource->delete($workplace->getId(), true);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
