<?php

declare(strict_types=1);

namespace App\User\Domain\Repository\Interfaces;

use App\User\Domain\Entity\User;

/**
 * @package App\Follow
 */

interface FollowRepositoryInterface
{
    /**
     * @return array<string, int>
     */
    public function getFollowStatuses(User $user): array;
}
