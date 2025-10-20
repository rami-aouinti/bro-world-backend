<?php

declare(strict_types=1);

namespace App\Messenger\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Application\DTO\Reaction\Reaction as ReactionDto;
use App\Messenger\Domain\Entity\Reaction as Entity;
use App\Messenger\Domain\Repository\Interfaces\ReactionDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\ReactionRepositoryInterface as Repository;
use App\Messenger\Infrastructure\Document\ReactionDocument;
use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use Override;

/**
 * @package App\Messenger
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id, ?string $entityManagerName = null)
 * @method \App\Messenger\Infrastructure\Repository\ReactionRepository getRepository()
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
class ReactionResource extends RestResource
{
    /**
     * @param \App\Messenger\Infrastructure\Repository\ReactionRepository $repository
     */
    public function __construct(
        Repository $repository,
        private readonly ReactionDocumentRepositoryInterface $reactionDocumentRepository,
        private readonly DocumentManagerInterface $documentManager,
    ) {
        parent::__construct($repository);

        $this->setDtoClass(ReactionDto::class);
    }

    #[Override]
    public function afterSave(EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $documentClass = $this->reactionDocumentRepository->getDocumentName();
        $this->documentManager->clear($documentClass);

        $document = $this->reactionDocumentRepository->find($entity->getId());

        if ($document instanceof ReactionDocument) {
            $document->refreshFromEntity($entity);
        } else {
            $document = ReactionDocument::fromEntity($entity);
        }

        $this->reactionDocumentRepository->save($document);
    }

    #[Override]
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $document = $this->reactionDocumentRepository->find($id);

        if ($document instanceof ReactionDocument) {
            $this->reactionDocumentRepository->remove($document);
        }
    }
}
