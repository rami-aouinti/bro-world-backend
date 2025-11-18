<?php

declare(strict_types=1);

namespace App\Messenger\Application\MessageHandler;

use App\Messenger\Application\Message\MarkConversationAsRead;
use App\Messenger\Application\Service\Interfaces\ConversationMessageCacheServiceInterface;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\MessageStatus;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\Messenger\Domain\Repository\Interfaces\ConversationRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageStatusRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @package App\Messenger
 */
#[AsMessageHandler]
readonly class MarkConversationAsReadHandler
{
    public function __construct(
        private ConversationRepositoryInterface $conversationRepository,
        private UserRepositoryInterface $userRepository,
        private MessageRepositoryInterface $messageRepository,
        private MessageStatusRepositoryInterface $messageStatusRepository,
        private ConversationMessageCacheServiceInterface $conversationMessageCacheService,
    ) {
    }

    public function __invoke(MarkConversationAsRead $message): void
    {
        $conversation = $this->conversationRepository->find($message->conversationId);
        $user = $this->userRepository->find($message->userId);

        if (!$conversation instanceof Conversation || !$user instanceof User) {
            return;
        }

        if (!$conversation->getParticipants()->contains($user)) {
            return;
        }

        $messages = $this->messageRepository->findBy(['conversation' => $conversation]);

        foreach ($messages as $entity) {
            $messageStatus = $this->messageStatusRepository->findOneBy([
                'message' => $entity,
                'user' => $user,
            ]);

            if ($messageStatus instanceof MessageStatus && $messageStatus->getStatus() !== MessageStatusType::READ) {
                $messageStatus->setStatus(MessageStatusType::READ);
                $this->messageStatusRepository->save($messageStatus);
            }
        }

        $this->conversationMessageCacheService->invalidateConversation($conversation);
    }
}
