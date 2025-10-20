<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Messenger\Domain\Repository\Interfaces\MessageDocumentRepositoryInterface;
use App\Messenger\Infrastructure\Document\MessageDocument;

class MessageDocumentRepository extends BaseDocumentRepository implements MessageDocumentRepositoryInterface
{
    protected static string $documentClass = MessageDocument::class;
}
