<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\DTO\Plugin\Plugin as PluginDto;
use App\User\Application\Resource\PluginResource;
use App\User\Domain\Entity\Plugin as Entity;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserPlugin;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Class PluginController
 *
 * @package App\User\Transport\Controller\Api\v1\Plugin
 *
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class PluginController
{
    public function __construct(
        private PluginResource $pluginResource,
        private EntityManagerInterface $entityManager,
        private ResponseHandler $responseHandler,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
    #[Route('/v1/profile/plugins', name: 'api_profile_plugins', methods: ['GET'])]
    public function __invoke(Request $request, User $loggedInUser): Response
    {
        $plugins = $this->pluginResource->find();
        $userPlugins = $this->entityManager->getRepository(UserPlugin::class)->findBy(['user' => $loggedInUser]);

        $activePluginMap = [];
        foreach ($userPlugins as $userPlugin) {
            $activePluginMap[$userPlugin->getPlugin()->getId()] = $userPlugin->isEnabled();
        }

        $dtos = array_map(
            function (Entity $plugin) use ($activePluginMap): PluginDto {
                $dto = (new PluginDto())->load($plugin);

                if (isset($activePluginMap[$plugin->getId()])) {
                    $dto->setActive($activePluginMap[$plugin->getId()]);
                }

                return $dto;
            },
            $plugins,
        );

        return $this->responseHandler->createResponse($request, $dtos);
    }
}
