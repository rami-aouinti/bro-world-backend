<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Document\Repository;

use App\General\Domain\Repository\Interfaces\DocumentRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Query\Builder;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @package App\General
 */
#[AutoconfigureTag('app.stopwatch')]
abstract class BaseDocumentRepository extends ServiceDocumentRepository implements DocumentRepositoryInterface
{
    /**
     * @var class-string
     */
    protected static string $documentClass;

    public function __construct(protected MongoManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, static::$documentClass);
    }

    public function getDocumentName(): string
    {
        return static::$documentClass;
    }

    public function getDocumentManager(?string $documentManagerName = null): DocumentManager
    {
        $manager = $documentManagerName === null
            ? $this->managerRegistry->getManagerForClass(static::$documentClass)
            : $this->managerRegistry->getManager($documentManagerName);

        if (!$manager instanceof DocumentManager) {
            throw new LogicException('Unable to resolve MongoDB document manager instance.');
        }

        return $manager;
    }

    public function getRepository(?string $documentManagerName = null): self
    {
        $repository = $this->getDocumentManager($documentManagerName)->getRepository(static::$documentClass);

        if (!$repository instanceof self) {
            throw new LogicException('Invalid document repository type resolved from document manager.');
        }

        return $repository;
    }

    public function save(object $document, ?bool $flush = null, ?string $documentManagerName = null): self
    {
        $flush ??= true;
        $documentManager = $this->getDocumentManager($documentManagerName);
        $documentManager->persist($document);

        if ($flush) {
            $documentManager->flush();
        }

        return $this;
    }

    public function remove(object $document, ?bool $flush = null, ?string $documentManagerName = null): self
    {
        $flush ??= true;
        $documentManager = $this->getDocumentManager($documentManagerName);
        $documentManager->remove($document);

        if ($flush) {
            $documentManager->flush();
        }

        return $this;
    }

    public function find(
        mixed $id,
        int $lockMode = LockMode::NONE,
        ?int $lockVersion = null,
        ?string $documentManagerName = null
    ): ?object
    {
        return $this->getRepository($documentManagerName)->find($id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, ?array $orderBy = null, ?string $documentManagerName = null): ?object
    {
        return $this->getRepository($documentManagerName)->findOneBy($criteria, $orderBy ?? []);
    }

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        $limit = null,
        $skip = null,
        ?string $documentManagerName = null
    ): array {
        return $this->getRepository($documentManagerName)->findBy($criteria, $orderBy ?? [], $limit, $skip);
    }

    public function findAll(?string $documentManagerName = null): array
    {
        return $this->getRepository($documentManagerName)->findAll();
    }

    public function createQueryBuilder(
        ?string $alias = null,
        ?string $documentManagerName = null
    ): Builder {
        unset($alias);

        return $this
            ->getDocumentManager($documentManagerName)
            ->createQueryBuilder(static::$documentClass);
    }
}
