<?php

declare(strict_types=1);

namespace App\Media\Application\Service;

use App\Log\Application\Service\CorrelationIdProvider;
use App\Media\Domain\Entity\File;
use App\Media\Domain\Message\DelayedMediaDeletionMessage;
use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

use function ltrim;
use function parse_url;
use function sprintf;

/**
 * @package App\Media
 */
class MediaDeletionScheduler
{
    private readonly DateInterval $delayInterval;
    private readonly int $delayMilliseconds;

    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly CorrelationIdProvider $correlationIdProvider,
        private readonly int $mediaDeletionDelayDays,
    ) {
        $this->delayInterval = new DateInterval(sprintf('P%dD', $this->mediaDeletionDelayDays));
        $this->delayMilliseconds = $this->mediaDeletionDelayDays * 24 * 60 * 60 * 1000;
    }

    public function schedule(File $file): void
    {
        $scheduledAt = $this->clock->now()->add($this->delayInterval);
        $message = new DelayedMediaDeletionMessage(
            $file->getId(),
            $this->extractBlobPath($file->getUrl()),
            $scheduledAt,
        );

        $this->logger->info('Scheduled delayed media purge.', [
            'file_id' => $file->getId(),
            'blob_path' => $message->getBlobPath(),
            'scheduled_at' => $scheduledAt->format(DateTimeImmutable::ATOM),
            'correlation_id' => $this->correlationIdProvider->getCorrelationId(),
        ]);

        $this->bus->dispatch($message, [new DelayStamp($this->delayMilliseconds)]);
    }

    private function extractBlobPath(string $url): string
    {
        $components = parse_url($url);
        $path = $components['path'] ?? $url;

        return ltrim($path, '/');
    }
}
