<?php

declare(strict_types=1);

namespace App\User\Domain\Repository\Interfaces;

use App\User\Domain\Entity\User;

/**
 * @package App\Story
 */
interface StoryRepositoryInterface
{
    public function availableStories(User $user): array;
}
