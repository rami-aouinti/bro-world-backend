<?php

declare(strict_types=1);

namespace App\Log\Application\Monolog;

use App\Log\Application\Service\CorrelationIdProvider;

/**
 * @package App\Log
 */
class CorrelationIdProcessor
{
    public function __construct(private readonly CorrelationIdProvider $provider)
    {
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        $correlationId = $this->provider->getCorrelationId();

        if ($correlationId !== null) {
            $record['extra']['correlation_id'] = $correlationId;
            $record['context']['correlation_id'] = $correlationId;
        }

        return $record;
    }
}
