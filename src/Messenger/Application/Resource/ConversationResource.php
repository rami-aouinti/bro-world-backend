<?php

declare(strict_types=1);

namespace App\Messenger\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Messenger\Application\DTO\Conversation\Conversation as ConversationDto;
use App\Messenger\Domain\Entity\Conversation as Entity;
use App\Messenger\Domain\Repository\Interfaces\ConversationDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\ConversationRepositoryInterface as Repository;
use App\Messenger\Infrastructure\Document\ConversationDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Override;
use App\User\Domain\Entity\User;
use Throwable;

/**
 * @package App\Messenger
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id, ?string $entityManagerName = null)
 * @method \App\Messenger\Infrastructure\Repository\ConversationRepository getRepository()
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
class ConversationResource extends RestResource
{
    /**
     * @param \App\Messenger\Infrastructure\Repository\ConversationRepository $repository
     */
    public function __construct(
        Repository $repository,
        private readonly ConversationDocumentRepositoryInterface $conversationDocumentRepository,
        private readonly DocumentManager $documentManager,
    ) {
        parent::__construct($repository);

        $this->setDtoClass(ConversationDto::class);
    }

    /**
     * @return array<int, Entity>
     *
     * @throws Throwable
     */
    public function findForUser(User $user): array
    {
        return $this->getRepository()->findByParticipantId($user);
    }

    #[Override]
    public function afterSave(EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $documentClass = $this->conversationDocumentRepository->getDocumentName();
        $this->documentManager->clear($documentClass);

        $document = $this->conversationDocumentRepository->find($entity->getId());

        if ($document instanceof ConversationDocument) {
            $document->refreshFromEntity($entity);
        } else {
            $document = ConversationDocument::fromEntity($entity);
        }

        $this->conversationDocumentRepository->save($document);
    }

    #[Override]
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $document = $this->conversationDocumentRepository->find($id);

        if ($document instanceof ConversationDocument) {
            $this->conversationDocumentRepository->remove($document);
        }
    }
}
