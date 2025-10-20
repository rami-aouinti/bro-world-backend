<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Messenger\Domain\Repository\Interfaces\ConversationDocumentRepositoryInterface;
use App\Messenger\Infrastructure\Document\ConversationDocument;

class ConversationDocumentRepository extends BaseDocumentRepository implements ConversationDocumentRepositoryInterface
{
    protected static string $documentClass = ConversationDocument::class;
}
