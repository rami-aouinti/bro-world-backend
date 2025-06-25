<?php

declare(strict_types=1);

namespace App\Tool\Transport\Controller\Api;

use App\User\Domain\Entity\Plugin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Class PluginController
 *
 * @package App\Tool\Infrastructure\Controller
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Tools')]
readonly class PluginController
{

    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    #[Route('/plugins', name: 'api_plugins_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $plugins = $this->em->getRepository(Plugin::class)->findAll();

        return new JsonResponse($plugins);
    }
}
