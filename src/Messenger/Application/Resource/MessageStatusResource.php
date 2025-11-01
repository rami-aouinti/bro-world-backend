<?php

declare(strict_types=1);

namespace App\Messenger\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Application\DTO\MessageStatus\MessageStatus as MessageStatusDto;
use App\Messenger\Domain\Entity\MessageStatus as Entity;
use App\Messenger\Domain\Repository\Interfaces\MessageStatusDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageStatusRepositoryInterface as Repository;
use App\Messenger\Infrastructure\Document\MessageStatusDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Override;

/**
 * @package App\Messenger
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id, ?string $entityManagerName = null)
 * @method Repository getRepository()
 * @method Entity[] find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null, ?string $entityManagerName = null)
 * @method Entity|null findOne(string $id, ?bool $throwExceptionIfNotFound = null, ?string $entityManagerName = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?bool $throwExceptionIfNotFound = null, ?string $entityManagerName = null)
 * @method Entity create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity patch(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity delete(string $id, ?bool $flush = null, ?string $entityManagerName = null)
 * @method Entity save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 *
 * @codingStandardsIgnoreEnd
 */
class MessageStatusResource extends RestResource
{
    public function __construct(
        Repository $repository,
        private readonly MessageStatusDocumentRepositoryInterface $messageStatusDocumentRepository,
        private readonly DocumentManager $documentManager,
    ) {
        parent::__construct($repository);

        $this->setDtoClass(MessageStatusDto::class);
    }

    #[Override]
    public function afterSave(EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $documentClass = $this->messageStatusDocumentRepository->getDocumentName();
        $this->documentManager->clear($documentClass);

        $document = $this->messageStatusDocumentRepository->find($entity->getId());

        if ($document instanceof MessageStatusDocument) {
            $document->refreshFromEntity($entity);
        } else {
            $document = MessageStatusDocument::fromEntity($entity);
        }

        $this->messageStatusDocumentRepository->save($document);
    }

    #[Override]
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $document = $this->messageStatusDocumentRepository->find($id);

        if ($document instanceof MessageStatusDocument) {
            $this->messageStatusDocumentRepository->remove($document);
        }
    }
}
