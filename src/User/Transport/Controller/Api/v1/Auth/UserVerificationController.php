<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @package App\User\User
 */
#[AsController]
#[OA\Tag(name: 'Authentication')]
readonly class UserVerificationController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Get user Json Web Token (JWT) for authentication.
     *
     * @param Request $request
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws \JsonException
     * @return JsonResponse
     */
    #[Route(
        path: '/v1/auth/verification_email',
        methods: [Request::METHOD_POST],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse([
                'message' => 'Token empty',
            ], 400);
        }

        $user = $this->userRepository->findOneBy([
            'verificationToken' => $token,
        ]);

        if (!$user) {
            return new JsonResponse([
                'message' => 'User not found or token is invalid',
            ], 400);
        }

        $user->setVerificationToken(null);
        $user->setEnabled(true);

        try {
            $this->userRepository->save($user);
        } catch (OptimisticLockException) {
        } catch (ORMException $e) {
            throw new ORMException($e->getMessage());
        }

        return new JsonResponse([
            'message' => 'Email verified successfully',
        ], 200);
    }
}
