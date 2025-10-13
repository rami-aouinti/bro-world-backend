<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Repository;

use App\General\Domain\Entity\IdempotencyKey as Entity;
use App\General\Domain\Repository\Interfaces\IdempotencyKeyRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @package App\General
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 */
class IdempotencyKeyRepository extends BaseRepository implements IdempotencyKeyRepositoryInterface
{
    protected static string $entityName = Entity::class;

    public function __construct(protected ManagerRegistry $managerRegistry)
    {
    }

    public function findOneByKey(string $key, ?string $entityManagerName = null): ?Entity
    {
        $entity = $this->findOneBy(['key' => $key], entityManagerName: $entityManagerName);

        return $entity instanceof Entity ? $entity : null;
    }

    public function purgeExpired(DateTimeImmutable $reference, ?string $entityManagerName = null): int
    {
        $queryBuilder = $this->getEntityManager($entityManagerName)
            ->createQueryBuilder()
            ->delete(static::$entityName, 'idempotencyKey')
            ->where('idempotencyKey.expiresAt IS NOT NULL')
            ->andWhere('idempotencyKey.expiresAt <= :reference')
            ->setParameter('reference', $reference);

        return (int)$queryBuilder->getQuery()->execute();
    }
}
