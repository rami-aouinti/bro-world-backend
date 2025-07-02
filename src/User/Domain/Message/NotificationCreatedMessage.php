<?php

declare(strict_types=1);

namespace App\User\Domain\Message;

use App\General\Domain\Message\Interfaces\MessageHighInterface;

/**
 * Class NotificationCreatedMessage
 *
 * @package App\User\Domain\Message
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class NotificationCreatedMessage implements MessageHighInterface
{
    public function __construct(
        private string $userId,
        private string $itemId,
        private string $token
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
