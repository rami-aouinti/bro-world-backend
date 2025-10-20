<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Log\Domain\Repository\Interfaces\LogLoginDocumentRepositoryInterface;
use App\Log\Infrastructure\Document\LogLoginDocument;

class LogLoginDocumentRepository extends BaseDocumentRepository implements LogLoginDocumentRepositoryInterface
{
    protected static string $documentClass = LogLoginDocument::class;
}
