<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\User\Domain\Entity\User;

/**
 * Event dispatched right after a user has been persisted so that
 * asynchronous listeners can trigger side effects (eg. configuration sync).
 */
final class UserConfigurationEvent
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $token
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }
}
