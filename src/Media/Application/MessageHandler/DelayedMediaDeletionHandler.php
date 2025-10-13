<?php

declare(strict_types=1);

namespace App\Media\Application\MessageHandler;

use App\Media\Application\Storage\MediaStorageInterface;
use App\Media\Domain\Message\DelayedMediaDeletionMessage;
use App\Media\Infrastructure\Repository\FileRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * @package App\Media
 */
#[AsMessageHandler]
class DelayedMediaDeletionHandler
{
    public function __construct(
        private readonly FileRepository $repository,
        private readonly MediaStorageInterface $mediaStorage,
        private readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(DelayedMediaDeletionMessage $message): void
    {
        try {
            $this->handle($message);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to purge media from storage.', [
                'file_id' => $message->getFileId(),
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function handle(DelayedMediaDeletionMessage $message): void
    {
        $file = $this->repository->find($message->getFileId());

        if ($file !== null) {
            $this->logger->info('Skipping purge, media entity still exists.', [
                'file_id' => $message->getFileId(),
            ]);

            return;
        }

        $scheduledAt = $message->getScheduledAt();
        $now = $this->clock->now();

        if ($scheduledAt > $now) {
            $this->logger->warning('Received purge message before schedule time, rescheduling skipped.', [
                'file_id' => $message->getFileId(),
                'scheduled_at' => $scheduledAt->format('c'),
                'now' => $now->format('c'),
            ]);

            return;
        }

        $this->mediaStorage->delete($message->getBlobPath());

        $this->logger->info('Media blob deleted from Azure storage.', [
            'file_id' => $message->getFileId(),
            'blob_path' => $message->getBlobPath(),
        ]);
    }
}
