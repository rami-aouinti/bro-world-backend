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
                $extra = $record->extra;
                $extra['correlation_id'] = $correlationId;

                $context = $record->context;
                $context['correlation_id'] = $correlationId;

                $record = $this->cloneRecordWith($record, $context, $extra);
            } else {
                $record['extra']['correlation_id'] = $correlationId;
                $record['context']['correlation_id'] = $correlationId;
            }
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function cloneRecordWith(LogRecord $record, array $context, array $extra): LogRecord
    {
        if (property_exists($record, 'formatted')) {
            return new LogRecord(
                datetime: $record->datetime,
                channel: $record->channel,
                level: $record->level,
                message: $record->message,
                context: $context,
                extra: $extra,
                formatted: $record->formatted,
            );
        }

        return new LogRecord(
            datetime: $record->datetime,
            channel: $record->channel,
            level: $record->level,
            message: $record->message,
            context: $context,
            extra: $extra,
        );
    }
}
