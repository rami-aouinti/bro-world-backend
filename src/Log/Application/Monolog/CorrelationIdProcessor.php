<?php

declare(strict_types=1);

namespace App\Log\Application\Monolog;

use App\Log\Application\Service\CorrelationIdProvider;
use Monolog\LogRecord;

/**
 * @package App\Log
 */
class CorrelationIdProcessor
{
    public function __construct(private readonly CorrelationIdProvider $provider)
    {
    }

    /**
     * @param array<string, mixed>|LogRecord $record
     *
     * @return array<string, mixed>|LogRecord
     */
    public function __invoke(LogRecord|array $record): LogRecord|array
    {
        $correlationId = $this->provider->getCorrelationId();

        if ($correlationId !== null) {
            if ($record instanceof LogRecord) {
                $record->extra['correlation_id'] = $correlationId;
                $record->context['correlation_id'] = $correlationId;
            } else {
                $record['extra']['correlation_id'] = $correlationId;
                $record['context']['correlation_id'] = $correlationId;
            }
        }

        return $record;
    }
}
