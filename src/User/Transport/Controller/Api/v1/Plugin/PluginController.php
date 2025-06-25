<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\General\Domain\Utils\JSON;
use OpenApi\Attributes as OA;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserPlugin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class ActivatePluginController
 *
 * @package App\User\Transport\Controller\Api\v1\Plugin
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class PluginController
{
    public function __construct(
        private SerializerInterface $serializer,
    )
    {
    }

    /**
     * @throws Throwable
     */
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    #[Route('/v1/profile/plugins', name: 'api_profile_plugins', methods: ['GET'])]
    public function __invoke(
        User $loggedInUser,
        EntityManagerInterface $em
    ): JsonResponse {
        $userPlugins = $em->getRepository(UserPlugin::class)->findBy(['user' => $loggedInUser]);

        $output = JSON::decode(
            $this->serializer->serialize(
                $userPlugins,
                'json',
                [
                    'groups' => 'UserPlugin',
                ]
            ),
            true,
        );
        return new JsonResponse($output);
    }
}
