<?php

declare(strict_types=1);

namespace App\Messenger\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Application\DTO\Message\Message as MessageDto;
use App\Messenger\Application\Service\Interfaces\ConversationMessageCacheServiceInterface;
use App\Messenger\Domain\Entity\Message as Entity;
use App\Messenger\Domain\Repository\Interfaces\MessageDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageRepositoryInterface as Repository;
use App\Messenger\Infrastructure\Document\MessageDocument;
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
class MessageResource extends RestResource
{
    public function __construct(
        Repository $repository,
        private readonly MessageDocumentRepositoryInterface $messageDocumentRepository,
        private readonly DocumentManager $documentManager,
        private readonly ConversationMessageCacheServiceInterface $conversationMessageCacheService,
    ) {
        parent::__construct($repository);

        $this->setDtoClass(MessageDto::class);
    }

    #[Override]
    public function afterSave(EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $documentClass = $this->messageDocumentRepository->getDocumentName();
        $this->documentManager->clear($documentClass);

        $document = $this->messageDocumentRepository->find($entity->getId());

        if ($document instanceof MessageDocument) {
            $document->refreshFromEntity($entity);
        } else {
            $document = MessageDocument::fromEntity($entity);
        }

        $this->messageDocumentRepository->save($document);
        $this->conversationMessageCacheService->invalidateConversation($entity->getConversation());
    }

    #[Override]
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $document = $this->messageDocumentRepository->find($id);

        if ($document instanceof MessageDocument) {
            $this->messageDocumentRepository->remove($document);
        }

        $this->conversationMessageCacheService->invalidateConversation($entity->getConversation());
    }
}
