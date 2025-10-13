<?php

declare(strict_types=1);

namespace App\Media\Domain\Message;

use App\General\Domain\Message\Interfaces\MessageLowInterface;
use DateTimeImmutable;

/**
 * @package App\Media
 */
class DelayedMediaDeletionMessage implements MessageLowInterface
{
    public function __construct(
        private readonly string $fileId,
        private readonly string $blobPath,
        private readonly DateTimeImmutable $scheduledAt,
    ) {
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getBlobPath(): string
    {
        return $this->blobPath;
    }

    public function getScheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }
}
