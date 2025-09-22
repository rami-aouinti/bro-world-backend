<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use RuntimeException;
use Throwable;

final class StoryUploadException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function missingFile(): self
    {
        return new self('No file provided for story upload.', 400);
    }

    public static function moveFailed(?Throwable $previous = null): self
    {
        return new self('Failed to store story file.', 500, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
