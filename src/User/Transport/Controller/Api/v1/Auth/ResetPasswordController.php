<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Auth;

use App\User\Application\Resource\UserResource;
use App\User\Application\Security\SecurityUser;
use App\User\Application\Service\Interfaces\UserRegistrationMailerInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

/**
 * Class ResetPasswordController
 *
 * @package App\User\Transport\Controller\Api\v1\Auth
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
final readonly class ResetPasswordController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserResource $userResource,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRegistrationMailerInterface $registrationMailer
    ) {}

    /**
     * @throws NotSupported
     * @throws Throwable
     */
    #[Route('/api/auth/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] array $payload
    ): JsonResponse {
        $email = $payload['email'] ?? null;
        $token = $payload['token'] ?? null;
        $password = $payload['password'] ?? null;
        $confirmPassword = $payload['confirmPassword'] ?? null;

        if (!$token || !$password || !$confirmPassword) {
            return new JsonResponse(['message' => 'Missing parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($password !== $confirmPassword) {
            return new JsonResponse(['message' => 'Passwords do not match.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy([
            'email' => $email
        ]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid or expired token.'], Response::HTTP_BAD_REQUEST);
        }

        $callback = fn (string $plainPassword): string => $this->passwordHasher
            ->hashPassword(new SecurityUser($user, []), $plainPassword);
        $user->setPassword($callback, $password);
        $user->setVerificationToken($token);
        $frontendUrl = 'https://bro-world-space.com/reset-password?token=' . $user->getVerificationToken();
        $this->registrationMailer->sendVerificationPassword($user, $frontendUrl);
        $this->userResource->save($user);

        return new JsonResponse(['message' => 'Password reset successfully.'], Response::HTTP_OK);
    }
}
