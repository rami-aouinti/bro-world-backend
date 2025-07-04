<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\File;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\File;
use App\Media\Domain\Enum\FileType;
use App\Media\Infrastructure\Repository\FileRepository;
use App\Media\Infrastructure\Repository\FolderRepository;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
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
use Throwable;

/**
 * @package App\File
 */
#[AsController]
#[OA\Tag(name: 'File')]
readonly class CreateFileController
{
    public function __construct(
        private SerializerInterface $serializer,
        private UserService $userService,
        private FileRepository $fileRepository,
        private FolderRepository $repository
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
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/file',
        methods: [Request::METHOD_POST],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Request $request): JsonResponse
    {
        $medias = $request->files->all() ? $this->userService->createMedia($request, 'media') : [];

        foreach ($medias as $media) {
            $data = $request->request->all();
            $file = new File();
            $file->setName($media['fileName']);
            $file->setUrl($media['path']);
            $file->setSize($media['fileSize']);
            $type = FileType::fromExtension(pathinfo($media['fileName'], PATHINFO_EXTENSION));
            $file->setType($type);
            $file->setExtension(pathinfo($media['fileName'], PATHINFO_EXTENSION));
            $file->setUser($loggedInUser);
            $file->setIsFavorite($data['isFavorite']?? false);
            $file->setIsPrivate($data['isPrivate'] ?? false);

            $this->fileRepository->save($file);
        }

        $output = JSON::decode(
            $this->serializer->serialize(
                $this->repository->findBy([
                    'user' => $loggedInUser,
                    'parent' => null
                ]),
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
