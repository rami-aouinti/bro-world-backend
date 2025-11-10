<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Domain\Entity\User;
use App\Workplace\Application\DTO\Workplace\WorkplaceUpdate;
use App\Workplace\Application\Resource\WorkplaceResource;
use App\Workplace\Domain\Entity\Workplace;
use App\Workplace\Transport\Controller\Frontend\Traits\WorkplaceOwnershipTrait;
use AutoMapperPlus\AutoMapperInterface;
use AutoMapperPlus\Exception\UnregisteredMappingException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Class UpdateWorkplaceController
 */
#[AsController]
#[OA\Tag(name: 'Workplace Frontend')]
readonly class UpdateWorkplaceController
{
    use WorkplaceOwnershipTrait;

    public function __construct(
        private AutoMapperInterface $autoMapper,
        private ResponseHandler $responseHandler,
        private WorkplaceResource $workplaceResource,
    ) {
    }

    /**
     * @throws Throwable
     * @throws UnregisteredMappingException
     */
    #[Route(
        path: '/v1/frontend/workplaces/{workplace}',
        methods: [Request::METHOD_PUT],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Workplace $workplace, Request $request): Response
    {
        $this->assertOwnership($workplace, $loggedInUser);

        /** @var WorkplaceUpdate $dto */
        $dto = $this->autoMapper->map($request, WorkplaceUpdate::class);
        $workplace = $this->workplaceResource->update($workplace->getId(), $dto, true);

        return $this->responseHandler->createResponse(
            $request,
            $workplace,
            $this->workplaceResource,
        );
    }
}
