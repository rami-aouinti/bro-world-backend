<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserPlugin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;
use OpenApi\Attributes as OA;

/**
 * Class PluginController
 *
 * @package App\User\Transport\Controller\Api\v1\Plugin
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class PluginController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * @throws Throwable
     */
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    #[Route('/v1/profile/plugins', name: 'api_profile_plugins', methods: ['GET'])]
    public function __invoke(User $loggedInUser): JsonResponse
    {
        $pluginRepo = $this->em->getRepository(Plugin::class);
        $userPluginRepo = $this->em->getRepository(UserPlugin::class);

        $plugins = $pluginRepo->findAll();
        $userPlugins = $userPluginRepo->findBy(['user' => $loggedInUser]);

        $activePluginMap = [];
        foreach ($userPlugins as $userPlugin) {
            $activePluginMap[$userPlugin->getPlugin()->getId()] = $userPlugin->isEnabled();
        }

        $result = [];

        foreach ($plugins as $plugin) {
            $pluginId = $plugin->getId();

            $result[] = [
                'id' => $pluginId,
                'key' => $plugin->getKey(),
                'name' => $plugin->getName(),
                'description' => $plugin->getDescription(),
                'icon' => $plugin->getIcon(),
                'installed' => $plugin->isInstalled(),
                'link' => $plugin->getLink(),
                'pricing' => $plugin->getPricing(),
                'action' => $plugin->getAction(),
                'active' => $activePluginMap[$pluginId] ?? false,
            ];
        }

        return new JsonResponse($result);
    }
}
