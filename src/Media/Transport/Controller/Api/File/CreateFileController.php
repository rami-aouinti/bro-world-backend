<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\File;

use App\General\Transport\Rest\ResponseHandler;
use App\Media\Application\Resource\FileResource;
use App\Media\Application\Service\FileCreationService;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @package App\File
 */
#[AsController]
#[OA\Tag(name: 'File')]
readonly class CreateFileController
{
    public function __construct(
        private FileCreationService $fileCreationService,
        private ResponseHandler $responseHandler,
        private FileResource $fileResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     */
    #[Route(
        path: '/v1/file',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): Response
    {
        $createdFiles = $this->fileCreationService->create($loggedInUser, $request, null);

        return $this->responseHandler->createResponse(
            $request,
            $createdFiles,
            $this->fileResource,
        );
    }
}

