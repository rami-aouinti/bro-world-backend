<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\File;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\File;
use App\Media\Infrastructure\Repository\FileRepository;
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
 * @package App\File
 */
#[AsController]
#[OA\Tag(name: 'File')]
readonly class DeleteFileController
{
    public function __construct(
        private SerializerInterface $serializer,
        private FileRepository $fileRepository
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User $loggedInUser
     * @param File $file
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @throws ORMException
     * @throws OptimisticLockException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/file/{file}',
        methods: [Request::METHOD_DELETE],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, File $file): JsonResponse
    {
        $this->fileRepository->remove($file);
        $output = JSON::decode(
            $this->serializer->serialize(
                'success',
                'json',
                [
                    'groups' => 'File',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
