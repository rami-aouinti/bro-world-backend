<?php

declare(strict_types=1);

namespace App\User\Application\Service\Interfaces;

use App\User\Domain\Entity\Event;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\EventReminderWindow;

interface UserNotificationMailerInterface
{
    public function sendEventReminder(Event $event, EventReminderWindow $window): void;

    public function sendBirthdayGreeting(User $user): void;
}
