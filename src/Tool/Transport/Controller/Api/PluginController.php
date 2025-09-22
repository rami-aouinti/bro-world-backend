<?php

declare(strict_types=1);

namespace App\Tool\Transport\Controller\Api;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\DTO\Plugin\Plugin as PluginDto;
use App\User\Application\Resource\PluginResource;
use App\User\Domain\Entity\Plugin as Entity;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class PluginController
 *
 * @package App\Tool\Infrastructure\Controller
 *
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Tools')]
readonly class PluginController
{
    public function __construct(
        private PluginResource $pluginResource,
        private ResponseHandler $responseHandler,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route('/plugins', name: 'api_plugins_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $plugins = $this->pluginResource->find();
        $dtos = array_map(
            static fn (Entity $plugin): PluginDto => (new PluginDto())->load($plugin),
            $plugins,
        );

        return $this->responseHandler->createResponse($request, $dtos);
    }
}
