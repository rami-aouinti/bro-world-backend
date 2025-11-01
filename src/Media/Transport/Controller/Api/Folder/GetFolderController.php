<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\Media\Domain\Entity\Folder;
use App\General\Transport\Rest\ResponseHandler;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use App\Media\Application\Resource\FolderResource;

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class GetFolderController
{
    public function __construct(
        private ResponseHandler $responseHandler,
        private FolderResource $folderResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User   $loggedInUser
     * @param Folder $folder
     *
     * @return Response
     */
    #[Route(
        path: '/v1/folder/{folder}',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(Request $request, User $loggedInUser, Folder $folder): Response
    {
        return $this->responseHandler->createResponse(
            $request,
            $folder,
            $this->folderResource,
        );
    }
}
