<?php

declare(strict_types=1);

namespace App\Messenger\Domain\Enum;

/**
 * Class MessageStatusType
 *
 * @package App\Messenger\Domain\Enum
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
enum MessageStatusType: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
}
