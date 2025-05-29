<?php

declare(strict_types=1);

namespace App\User\Transport\MessageHandler;

use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Application\Service\Interfaces\UserRegistrationMailerInterface;
use App\User\Application\Service\UserCacheService;
use App\User\Application\Service\UserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Message\UserCreatedMessage;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * If you need handling multiple - follow https://symfony.com/doc/current/messenger.html#handling-multiple-messages
 *
 * @package App\Tool
 */
#[AsMessageHandler]
readonly class UserCreatedHandlerMessage
{
    public function __construct(
        private UserService $userService,
        private UserCacheService $userCacheService,
        private UserElasticsearchServiceInterface $userElasticsearchService,
        private UserRegistrationMailerInterface $registrationMailer
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(UserCreatedMessage $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * @throws Throwable
     */
    private function handleMessage(UserCreatedMessage $message): void
    {
        $user = $this->createUser(
            $message->getUserId(),
            $message->getUserData(),
            $message->getLanguage()
        );
        $this->generateEmail($user);
        $this->indexUser($user);
        $this->clearCache();
    }

    /**
     * @param string $userId
     * @param array  $userData
     * @param string $language
     *
     * @throws NonUniqueResultException
     * @throws Throwable
     * @return User
     */
    private function createUser(string $userId, array $userData, string $language): User
    {
        return $this->userService->generateUserIndex($userId, $userData, $language);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function clearCache(): void
    {
        $this->userCacheService->clear();
    }

    private function indexUser(User $user): void
    {
        $this->userElasticsearchService->indexUserInElasticsearch($user);
    }

    private function generateEmail(User $user): void
    {
        $frontendUrl = 'https://bro-world-space.com/verify-email?token=' . $user->getVerificationToken();
        $this->registrationMailer->sendVerificationEmail($user, $frontendUrl);
    }
}
