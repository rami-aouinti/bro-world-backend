<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\Media\Application\Resource\FolderResource;
use App\Media\Domain\Entity\Folder;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class DeleteFolderController
{
    public function __construct(
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
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Folder $folder): Response
    {
        $this->folderResource->delete($folder->getId(), true);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
