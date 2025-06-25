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
class PluginController
{
    /**
     * @throws Throwable
     */
    #[Route('/v1/profile/plugins', name: 'api_profile_plugins', methods: ['GET'])]
    public function __invoke(
        User $loggedInUser,
        EntityManagerInterface $em
    ): JsonResponse {
        $userPlugins = $em->getRepository(UserPlugin::class)->findBy(['user' => $loggedInUser]);

        return new JsonResponse($userPlugins, 200, ['groups' => ['UserPlugin']]);
    }
}
