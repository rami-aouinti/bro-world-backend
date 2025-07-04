<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\Folder;
use App\Media\Infrastructure\Repository\FolderRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class CreateFolderController
{
    public function __construct(
        private SerializerInterface $serializer,
        private FolderRepository $folderRepository
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User    $loggedInUser
     * @param Request $request
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws ORMException
     * @throws OptimisticLockException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/folder',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        $data = $request->request->all();
        $folder = new Folder();
        $folder->setName($data['name']);
        $folder->setUser($loggedInUser);
        $folder->setIsFavorite($data['isFavorite'] ?? false);
        $folder->setIsPrivate($data['isPrivate'] ?? false);

        $this->folderRepository->save($folder);
        $output = JSON::decode(
            $this->serializer->serialize(
                $folder,
                'json',
                [
                    'groups' => 'Folder',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
