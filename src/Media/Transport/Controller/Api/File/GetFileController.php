<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\File;

use App\Media\Domain\Entity\File;
use App\General\Transport\Rest\ResponseHandler;
use App\Media\Application\Resource\FileResource;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package App\File
 */
#[AsController]
#[OA\Tag(name: 'File')]
readonly class GetFileController
{
    public function __construct(
        private ResponseHandler $responseHandler,
        private FileResource $fileResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     * @param File $file
     *
     * @return Response
     */
    #[Route(
        path: '/v1/file/{file}',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(Request $request, User $loggedInUser, File $file): Response
    {
        return $this->responseHandler->createResponse(
            $request,
            $file,
            $this->fileResource,
        );
    }
}
