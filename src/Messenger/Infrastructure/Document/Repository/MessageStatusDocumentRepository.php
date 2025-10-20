<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Messenger\Domain\Repository\Interfaces\MessageStatusDocumentRepositoryInterface;
use App\Messenger\Infrastructure\Document\MessageStatusDocument;

class MessageStatusDocumentRepository extends BaseDocumentRepository implements MessageStatusDocumentRepositoryInterface
{
    protected static string $documentClass = MessageStatusDocument::class;
}
