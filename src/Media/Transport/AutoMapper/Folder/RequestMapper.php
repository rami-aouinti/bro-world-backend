<?php

declare(strict_types=1);

namespace App\Media\Transport\AutoMapper\Folder;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\Media\Application\Resource\FolderResource;
use App\Media\Domain\Entity\Folder as FolderEntity;
use InvalidArgumentException;
use Throwable;

use function filter_var;
use function is_bool;
use function is_int;
use function is_string;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * @package App\Media
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'name',
        'parent',
        'isPrivate',
        'isFavorite',
    ];

    public function __construct(private readonly FolderResource $folderResource)
    {
    }

    /**
     * @throws Throwable
     */
    protected function transformParent(?string $parent): ?FolderEntity
    {
        if ($parent === null || $parent === '') {
            return null;
        }

        return $this->folderResource->getReference($parent);
    }

    protected function transformIsPrivate(bool|int|string|null $isPrivate): bool
    {
        return $this->normalizeBoolean($isPrivate);
    }

    protected function transformIsFavorite(bool|int|string|null $isFavorite): bool
    {
        return $this->normalizeBoolean($isFavorite);
    }

    private function normalizeBoolean(bool|int|string|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if ($value === null || $value === '') {
            return false;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid boolean value');
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($normalized === null) {
            throw new InvalidArgumentException('Invalid boolean value');
        }

        return $normalized;
    }
}
