<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Log\Domain\Entity\LogLoginFailure as LogLoginFailureEntity;
use App\Log\Infrastructure\Document\Repository\LogLoginFailureDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'log_login_failure', repositoryClass: LogLoginFailureDocumentRepository::class)]
class LogLoginFailureDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $userId = null;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $timestamp;

    public static function fromEntity(LogLoginFailureEntity $entity): self
    {
        $document = new self($entity->getId());
        $document->userId = $entity->getUser()->getId();
        $document->timestamp = $entity->getTimestamp();
        $document->setCreatedAt($entity->getTimestamp());

        return $document;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}
