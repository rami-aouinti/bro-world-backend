<?php

declare(strict_types=1);

namespace App\Media\Transport\AutoMapper\File;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\Media\Application\Resource\FolderResource;
use App\Media\Domain\Entity\Folder as FolderEntity;
use App\Media\Domain\Enum\FileType;
use InvalidArgumentException;
use Throwable;

use function filter_var;
use function is_bool;
use function is_int;
use function is_string;
use function is_numeric;

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
        'type',
        'url',
        'isPrivate',
        'isFavorite',
        'extension',
        'size',
        'folder',
    ];

    public function __construct(private readonly FolderResource $folderResource)
    {
    }

    protected function transformType(string $type): FileType
    {
        return FileType::tryFrom($type) ?? throw new InvalidArgumentException('Invalid file type');
    }

    protected function transformIsPrivate(bool|int|string|null $isPrivate): bool
    {
        return $this->normalizeBoolean($isPrivate);
    }

    protected function transformIsFavorite(bool|int|string|null $isFavorite): bool
    {
        return $this->normalizeBoolean($isFavorite);
    }

    protected function transformSize(int|string $size): int
    {
        if (is_int($size)) {
            return $size;
        }

        if (!is_numeric($size)) {
            throw new InvalidArgumentException('Invalid size value');
        }

        return (int)$size;
    }

    /**
     * @throws Throwable
     */
    protected function transformFolder(?string $folder): ?FolderEntity
    {
        if ($folder === null || $folder === '') {
            return null;
        }

        return $this->folderResource->getReference($folder);
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
