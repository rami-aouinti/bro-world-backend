<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\General\Transport\Rest\ResponseHandler;
use App\Media\Application\DTO\Folder\FolderCreate;
use App\Media\Application\Resource\FolderResource;
use App\Media\Domain\Entity\Folder;
use App\User\Domain\Entity\User;
use AutoMapperPlus\Exception\UnregisteredMappingException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use AutoMapperPlus\AutoMapperInterface;
use Throwable;

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class CreateChildFolderController
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private ResponseHandler $responseHandler,
        private FolderResource $folderResource,
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User    $loggedInUser
     * @param Request $request
     * @param Folder  $oldFolder
     *
     * @throws UnregisteredMappingException
     * @throws Throwable
     * @return Response
     */
    #[Route(
        path: '/v1/folder/{oldFolder}',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request, Folder $oldFolder): Response
    {
        /** @var FolderCreate $dto */
        $dto = $this->autoMapper->map($request, FolderCreate::class);
        $dto->setUser($loggedInUser);
        $dto->setParent($oldFolder);

        $folder = $this->folderResource->create($dto, true);

        return $this->responseHandler->createResponse(
            $request,
            $folder,
            $this->folderResource,
        );
    }
}
