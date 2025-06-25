<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserPlugin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class ActivatePluginController
 *
 * @package App\User\Transport\Controller\Api\v1\Plugin
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class ActivatePluginController
{
    /**
     * @throws Throwable
     */
    #[Route('/v1/profile/plugin/{key}/toggle', name: 'api_user_plugin_toggle', methods: ['POST'])]
    public function __invoke(
        User $loggedInUser,
        string $key,
        EntityManagerInterface $em
    ): JsonResponse {

        $plugin = $em->getRepository(Plugin::class)->findOneBy(['key' => $key]);
        if (!$plugin) {
            return new JsonResponse(['error' => 'Plugin not found'], 404);
        }

        $repo = $em->getRepository(UserPlugin::class);
        $userPlugin = $repo->findOneBy(['user' => $loggedInUser, 'plugin' => $plugin]);

        if ($userPlugin) {
            $userPlugin->toggle();
        } else {
            $userPlugin = new UserPlugin($loggedInUser, $plugin);
            $em->persist($userPlugin);
        }

        $em->flush();

        return new JsonResponse($userPlugin, 200, ['groups' => ['UserPlugin']]);
    }
}
