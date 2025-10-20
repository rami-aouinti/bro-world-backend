<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Document\Repository;

use App\General\Infrastructure\Document\Repository\BaseDocumentRepository;
use App\Messenger\Domain\Repository\Interfaces\ReactionDocumentRepositoryInterface;
use App\Messenger\Infrastructure\Document\ReactionDocument;

class ReactionDocumentRepository extends BaseDocumentRepository implements ReactionDocumentRepositoryInterface
{
    protected static string $documentClass = ReactionDocument::class;
}
