<?php

declare(strict_types=1);

namespace App\General\Domain\Repository\Interfaces;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\Query\Builder;
use LogicException;

/**
 * @package App\General
 */
interface DocumentRepositoryInterface
{
    /**
     * Getter method for document class name.
     *
     * @return class-string
     */
    public function getDocumentName(): string;

    /**
     * Getter method for DocumentManager for current document.
     *
     * @throws LogicException
     */
    public function getDocumentManager(?string $documentManagerName = null): DocumentManager;

    /**
     * Getter method for Doctrine ODM document repository.
     */
    public function getRepository(?string $documentManagerName = null): self;

    /**
     * Saves (persists) document to MongoDB.
     */
    public function save(object $document, ?bool $flush = null, ?string $documentManagerName = null): self;

    /**
     * Removes document from MongoDB.
     */
    public function remove(object $document, ?bool $flush = null, ?string $documentManagerName = null): self;

    /**
     * Wrapper for default Doctrine ODM repository find method.
     */
    public function find(
        mixed $id,
        int $lockMode = LockMode::NONE,
        ?int $lockVersion = null,
        ?string $documentManagerName = null
    ): ?object;

    /**
     * Wrapper for default Doctrine ODM repository findOneBy method.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null, ?string $documentManagerName = null): ?object;

    /**
     * Wrapper for default Doctrine ODM repository findBy method.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $skip
     * @return array<int, object>
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        $limit = null,
        $skip = null,
        ?string $documentManagerName = null
    ): array;

    /**
     * Wrapper for default Doctrine ODM repository findAll method.
     *
     * @return array<int, object>
     */
    public function findAll(?string $documentManagerName = null): array;

    /**
     * Method to create new query builder for current document.
     */
    public function createQueryBuilder(
        ?string $alias = null,
        ?string $documentManagerName = null
    ): Builder;
}
