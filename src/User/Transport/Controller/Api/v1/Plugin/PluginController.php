<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\Plugin;
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
        private EntityManagerInterface $em
    )
    {
    }

    /**
     * @throws Throwable
     */
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    #[Route('/v1/profile/plugins', name: 'api_profile_plugins', methods: ['GET'])]
    public function __invoke(
        User $loggedInUser
    ): JsonResponse {
        $plugins = $this->em->getRepository(Plugin::class)->findAll();
        $userPlugins = $this->em->getRepository(UserPlugin::class)->findBy(['user' => $loggedInUser]);

        $result = [];
        foreach ($plugins as $key => $plugin) {
            foreach ($userPlugins as $userPlugin) {
                $result[$plugin->getId()][] = $plugin;
                if ($plugin->getId() === $userPlugin->getPlugin()->getId()) {
                    $result[$plugin->getId()]['active'] = true;
                } else {
                    $result[$plugin->getId()]['active'] = false;
                }
            }
        }

        $output = JSON::decode(
            $this->serializer->serialize(
                $result,
                'json',
                [
                    'groups' => 'Plugin',
                ]
            ),
            true,
        );
        return new JsonResponse($output);
    }
}
