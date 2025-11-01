<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\File;

use App\Media\Application\Resource\FileResource;
use App\Media\Domain\Entity\File;
use App\User\Domain\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\File
 */
#[AsController]
#[OA\Tag(name: 'File')]
readonly class DeleteFileController
{
    public function __construct(
        private FileResource $fileResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     * @param File $file
     *
     * @throws Throwable
     * @return Response
     */
    #[Route(
        path: '/v1/file/{file}',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, File $file): Response
    {
        $this->fileResource->delete($file->getId(), true);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
