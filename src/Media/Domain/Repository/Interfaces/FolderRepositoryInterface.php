<?php

declare(strict_types=1);

namespace App\Media\Domain\Repository\Interfaces;

use App\General\Domain\Repository\Interfaces\BaseRepositoryInterface;
use App\Media\Domain\Entity\Folder;
use App\User\Domain\Entity\User;

/**
 * @package App\Media
 */
interface FolderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return array<int, Folder>
     */
    public function findRootFoldersForUser(User $user): array;
}

