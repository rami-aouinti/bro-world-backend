<?php

declare(strict_types=1);

namespace App\User\Domain\Message;

use App\General\Domain\Message\Interfaces\MessageHighInterface;

/**
 * Class UserCreatedMessage
 *
 * @package App\User\Domain\Message
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserCreatedMessage implements MessageHighInterface
{
    public function __construct(
        private string $userId,
        private array $userData,
        private string $language
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}
