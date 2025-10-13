<?php

declare(strict_types=1);

namespace App\Media\Application\Storage;

/**
 * @package App\Media
 */
interface MediaStorageInterface
{
    public function delete(string $path): void;
}
