<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Log\Domain\Repository\Interfaces\LogLoginFailureDocumentRepositoryInterface;
use App\Log\Infrastructure\Document\LogLoginFailureDocument;

class LogLoginFailureDocumentRepository extends BaseDocumentRepository implements LogLoginFailureDocumentRepositoryInterface
{
    protected static string $documentClass = LogLoginFailureDocument::class;
}
