<?php

declare(strict_types=1);

namespace App\Media\Transport\AutoMapper\Folder;

use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;
use App\Media\Application\DTO\Folder\FolderCreate;
use App\Media\Application\DTO\Folder\FolderPatch;
use App\Media\Application\DTO\Folder\FolderUpdate;

/**
 * @package App\Media
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        FolderCreate::class,
        FolderUpdate::class,
        FolderPatch::class,
    ];

    public function __construct(RequestMapper $requestMapper)
    {
        parent::__construct($requestMapper);
    }
}
