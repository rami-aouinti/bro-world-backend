<?php

declare(strict_types=1);

namespace App\Workplace\Transport\AutoMapper\Workplace;

use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;
use App\Workplace\Application\DTO\Workplace\WorkplaceCreate;
use App\Workplace\Application\DTO\Workplace\WorkplacePatch;
use App\Workplace\Application\DTO\Workplace\WorkplaceUpdate;

/**
 * @package App\User
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * Classes to use specified request mapper.
     *
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        WorkplaceCreate::class,
        WorkplaceUpdate::class,
        WorkplacePatch::class,
    ];

    public function __construct(
        RequestMapper $requestMapper,
    ) {
        parent::__construct($requestMapper);
    }
}
