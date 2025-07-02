<?php

declare(strict_types=1);

namespace App\User\Transport\MessageHandler;

use App\User\Application\Service\NotificationService;
use App\User\Domain\Message\NotificationCreatedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * If you need handling multiple - follow https://symfony.com/doc/current/messenger.html#handling-multiple-messages
 *
 * @package App\Tool
 */
#[AsMessageHandler]
readonly class NotificationCreatedHandlerMessage
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(NotificationCreatedMessage $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * @throws Throwable
     */
    private function handleMessage(NotificationCreatedMessage $message): void
    {
        $this->notificationService->createNotificationStory(
            $message->getToken(),
            'PUSH',
            $message->getItemId()
        );
    }
}
