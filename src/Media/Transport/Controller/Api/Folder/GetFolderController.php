<?php

declare(strict_types=1);

namespace App\Media\Transport\Controller\Api\Folder;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\Folder;
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

/**
 * @package App\Folder
 */
#[AsController]
#[OA\Tag(name: 'Folder')]
readonly class GetFolderController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * Get current user profile data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param User   $loggedInUser
     * @param Folder $folder
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/folder/{folder}',
        methods: [Request::METHOD_GET],
    )]
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    public function __invoke(User $loggedInUser, Folder $folder): JsonResponse
    {
        $output = JSON::decode(
            $this->serializer->serialize(
                $loggedInUser,
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
