<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum EventReminderWindow: string
{
    case FOUR_HOURS = 'four_hours';
    case FIFTEEN_MINUTES = 'fifteen_minutes';
}
