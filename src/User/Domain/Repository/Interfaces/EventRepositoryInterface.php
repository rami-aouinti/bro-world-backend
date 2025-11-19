<?php

declare(strict_types=1);

namespace App\User\Domain\Repository\Interfaces;

use App\User\Domain\Entity\Event;
use DateTimeImmutable;

/**
 * @package App\Tool
 */
interface EventRepositoryInterface
{
    /**
     * @return array<int, Event>
     */
    public function findEventsWithinPeriod(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
