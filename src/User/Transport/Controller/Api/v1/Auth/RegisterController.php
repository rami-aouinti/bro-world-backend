<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\User\Application\Service\UserService;
use Doctrine\ORM\NonUniqueResultException;
use OpenApi\Attributes as OA;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;

/**
 * @package App\User\User
 */
#[AsController]
#[OA\Tag(name: 'Authentication')]
readonly class RegisterController
{
    public function __construct(
        private SerializerInterface $serializer,
        private UserService $userService
    ) {
    }

    /**
     * Get user Json Web Token (JWT) for authentication.
     *
     * @throws ExceptionInterface
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Throwable
     * @throws TransportExceptionInterface
     */
    #[Route(
        path: '/v1/auth/register',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->serialize(
                $this->userService->createUser($request),
                'json'
            )
        );
    }
}
