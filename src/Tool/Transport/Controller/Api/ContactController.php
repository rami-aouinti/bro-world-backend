<?php

declare(strict_types=1);

namespace App\Tool\Transport\Controller\Api;

use App\Tool\Application\Service\Interfaces\ContactServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @package App\Tool
 */
#[AsController]
#[OA\Tag(name: 'Tools')]
class ContactController
{
    public function __construct(
        private readonly ContactServiceInterface $contactService,
    ) {
    }

    /**
     * Route for application health check. This action will make some simple tasks to ensure that application is up
     * and running like expected.
     *
     * @see https://kubernetes.io/docs/tasks/configure-pod-container/configure-liveness-readiness-probes/
     *
     * @throws Throwable
     */
    #[Route(
        path: '/contact',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $this->contactService->send(
            $request->request->get('name', ''),
            $request->request->get('email', ''),
            $request->request->get('subject', ''),
            $request->request->get('message', '')
        );
        return new JsonResponse([
            'success' => true,
            'message' => 'Message sent successfully.',
        ], Response::HTTP_OK);
    }
}
