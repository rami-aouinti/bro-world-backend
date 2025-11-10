<?php

declare(strict_types=1);

namespace App\Workplace\Domain\Repository\Interfaces;

use App\User\Domain\Entity\User;
use App\Workplace\Domain\Entity\Workplace;

/**
 * @package App\Workplace
 */
interface WorkplaceRepositoryInterface
{
    /**
     * @return array<int, Workplace>
     */
    public function findByMember(User $user): array;

    public function findOneBySlugAndMember(User $user, string $slug): ?Workplace;
}
