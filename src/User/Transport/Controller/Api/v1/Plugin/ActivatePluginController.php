<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Plugin;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\DTO\Plugin\Plugin as PluginDto;
use App\User\Application\Resource\PluginResource;
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
 * Class ActivatePluginController
 *
 * @package App\User\Transport\Controller\Api\v1\Plugin
 *
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Profile')]
readonly class ActivatePluginController
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
    #[Route('/v1/profile/plugin/{key}/toggle', name: 'api_user_plugin_toggle', methods: ['POST'])]
    public function __invoke(Request $request, User $loggedInUser, string $key): Response
    {
        $plugin = $this->pluginResource->findOneBy(['key' => $key], throwExceptionIfNotFound: true);
        $repository = $this->entityManager->getRepository(UserPlugin::class);
        $userPlugin = $repository->findOneBy(['user' => $loggedInUser, 'plugin' => $plugin]);

        if ($userPlugin instanceof UserPlugin) {
            $userPlugin->toggle();
        } else {
            $userPlugin = new UserPlugin($loggedInUser, $plugin);
            $this->entityManager->persist($userPlugin);
        }

        $this->entityManager->flush();

        $dto = (new PluginDto())
            ->load($plugin)
            ->setActive($userPlugin->isEnabled());

        return $this->responseHandler->createResponse($request, $dto);
    }
}
