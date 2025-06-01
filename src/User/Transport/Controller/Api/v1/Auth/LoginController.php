<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\User\Application\ApiProxy\UserProxy;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


/**
 * @package App\User\User
 */
#[AsController]
#[OA\Tag(name: 'Authentication')]
readonly class LoginController
{
    public function __construct(
        private UserProxy $userProxy
    ) {
    }

    /**
     * Get user Json Web Token (JWT) for authentication.
     *
     * @param Request $request
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/auth/login',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $userRequest = $request->request->all();
        $response = [];
        $token = $this->userProxy->login(
            $userRequest['username'],
            $userRequest['password']
        );
        $response['token'] = $token['token'];
        $response['profile'] = $this->userProxy->profile($token['token']);

        return new JsonResponse(
            $response
        );
    }
}
