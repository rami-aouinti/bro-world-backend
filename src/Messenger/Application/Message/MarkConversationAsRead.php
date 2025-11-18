<?php

declare(strict_types=1);

namespace App\Messenger\Application\Message;

/**
 * @package App\Messenger
 */
readonly class MarkConversationAsRead
{
    public function __construct(
        public string $conversationId,
        public string $userId,
    ) {
    }
}
