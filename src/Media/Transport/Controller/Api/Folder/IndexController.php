<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\General\Transport\Rest\ResponseHandler;
use App\Media\Application\Resource\FolderResource;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class IndexController
{
    public function __construct(
        private ResponseHandler $responseHandler,
        private FolderResource $folderResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param Request $request
     * @param User    $loggedInUser
     *
     * @throws Throwable
     * @return Response
     */
    #[Route(
        path: '/v1/folder',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(Request $request, User $loggedInUser): Response
    {
        $folders = $this->folderResource->findRootFoldersForUser($loggedInUser);

        return $this->responseHandler->createResponse(
            $request,
            $folders,
            $this->folderResource,
        );
    }
}
