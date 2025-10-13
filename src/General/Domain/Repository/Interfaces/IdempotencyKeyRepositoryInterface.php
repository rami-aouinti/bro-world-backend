<?php

declare(strict_types=1);

namespace App\General\Domain\Repository\Interfaces;

use App\General\Domain\Entity\IdempotencyKey;
use DateTimeImmutable;

/**
 * @package App\General
 */
interface IdempotencyKeyRepositoryInterface extends BaseRepositoryInterface
{
    public function findOneByKey(string $key, ?string $entityManagerName = null): ?IdempotencyKey;

    public function purgeExpired(DateTimeImmutable $reference, ?string $entityManagerName = null): int;
}
