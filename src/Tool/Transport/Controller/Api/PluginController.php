<?php

declare(strict_types=1);

namespace App\Tool\Transport\Controller\Api;

use App\General\Domain\Utils\JSON;
use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

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
        private readonly SerializerInterface $serializer,
        private EntityManagerInterface $em
    )
    {
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    #[Route('/plugins', name: 'api_plugins_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $plugins = $this->em->getRepository(Plugin::class)->findAll();
        $output = JSON::decode(
            $this->serializer->serialize(
                $plugins,
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
