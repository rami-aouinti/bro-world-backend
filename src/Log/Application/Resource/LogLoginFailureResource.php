<?php

declare(strict_types=1);

namespace App\Log\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Log\Domain\Entity\LogLoginFailure as Entity;
use App\Log\Domain\Repository\Interfaces\LogLoginFailureDocumentRepositoryInterface;
use App\Log\Domain\Repository\Interfaces\LogLoginFailureRepositoryInterface as Repository;
use App\Log\Infrastructure\Document\LogLoginFailureDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Override;
use App\User\Domain\Entity\User;
use Throwable;

/**
 * @package App\Log
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id, ?string $entityManagerName = null)
 * @method \App\Log\Infrastructure\Repository\LogLoginFailureRepository getRepository()
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
class LogLoginFailureResource extends RestResource
{
    /**
     * @param \App\Log\Infrastructure\Repository\LogLoginFailureRepository $repository
     */
    public function __construct(
        Repository $repository,
        private readonly LogLoginFailureDocumentRepositoryInterface $logLoginFailureDocumentRepository,
        private readonly DocumentManager $documentManager,
    ) {
        parent::__construct($repository);
    }

    /**
     * Method to reset specified user log login failures.
     *
     * @throws Throwable
     */
    public function reset(User $user): void
    {
        $this->getRepository()->clear($user);

        $documents = $this->logLoginFailureDocumentRepository->findBy([
            'userId' => $user->getId(),
        ]);

        foreach ($documents as $document) {
            if ($document instanceof LogLoginFailureDocument) {
                $this->logLoginFailureDocumentRepository->remove($document);
            }
        }
    }

    #[Override]
    public function afterSave(EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $documentClass = $this->logLoginFailureDocumentRepository->getDocumentName();
        $this->documentManager->clear($documentClass);

        $document = LogLoginFailureDocument::fromEntity($entity);
        $this->logLoginFailureDocumentRepository->save($document);
    }

    #[Override]
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
        if (!$entity instanceof Entity) {
            return;
        }

        $document = $this->logLoginFailureDocumentRepository->find($id);

        if ($document instanceof LogLoginFailureDocument) {
            $this->logLoginFailureDocumentRepository->remove($document);
        }
    }
}
