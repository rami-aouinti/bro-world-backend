<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Log\Domain\Repository\Interfaces\LogRequestDocumentRepositoryInterface;
use App\Log\Infrastructure\Document\LogRequestDocument;

class LogRequestDocumentRepository extends BaseDocumentRepository implements LogRequestDocumentRepositoryInterface
{
    protected static string $documentClass = LogRequestDocument::class;
}
