<?php

declare(strict_types=1);

namespace App\Tool\Transport\Controller\Api;

use App\General\Transport\Rest\ResponseHandler;
use App\User\Application\DTO\Plugin\Plugin as PluginDto;
use App\User\Application\Resource\PluginResource;
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
readonly class GetPluginController
{
    public function __construct(
        private PluginResource $pluginResource,
        private ResponseHandler $responseHandler,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Route('/plugins/{key}', name: 'api_plugins_item', methods: ['GET'])]
    public function __invoke(Request $request, string $key): Response
    {
        $plugin = $this->pluginResource->findOneBy(['key' => $key], throwExceptionIfNotFound: true);

        return $this->responseHandler->createResponse($request, (new PluginDto())->load($plugin));
    }
}
