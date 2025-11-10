<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Domain\Entity\User;
use App\Workplace\Application\DTO\Workplace\WorkplaceCreate;
use App\Workplace\Application\Resource\WorkplaceResource;
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

#[AsController]
#[OA\Tag(name: 'Workplace Frontend')]
readonly class CreateWorkplaceController
{
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
        path: '/v1/frontend/workplaces',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): Response
    {
        /** @var WorkplaceCreate $dto */
        $dto = $this->autoMapper->map($request, WorkplaceCreate::class);
        $dto->setOwner($loggedInUser);

        $workplace = $this->workplaceResource->create($dto, true);

        return $this->responseHandler->createResponse(
            $request,
            $workplace,
            $this->workplaceResource,
        );
    }
}
