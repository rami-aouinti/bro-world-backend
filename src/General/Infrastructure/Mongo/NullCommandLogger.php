<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Mongo;

/**
 * Null object for Doctrine MongoDB command logger.
 */
class NullCommandLogger
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getExecutedCommands(): array
    {
        return [];
    }

    public function clear(): void
    {
        // noop
    }
}
