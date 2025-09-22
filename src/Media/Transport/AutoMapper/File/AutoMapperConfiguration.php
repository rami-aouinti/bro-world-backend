<?php

declare(strict_types=1);

namespace App\Media\Transport\AutoMapper\File;

use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;
use App\Media\Application\DTO\File\FileCreate;
use App\Media\Application\DTO\File\FilePatch;
use App\Media\Application\DTO\File\FileUpdate;

/**
 * @package App\Media
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        FileCreate::class,
        FileUpdate::class,
        FilePatch::class,
    ];

    public function __construct(RequestMapper $requestMapper)
    {
        parent::__construct($requestMapper);
    }
}
