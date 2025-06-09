<?php

declare(strict_types=1);

namespace App\User\Transport\AutoMapper\Review;

use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;
use App\User\Application\DTO\Review\ReviewCreate;
use App\User\Application\DTO\Review\ReviewPatch;
use App\User\Application\DTO\Review\ReviewUpdate;

/**
 * @package App\Review
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * Classes to use specified request mapper.
     *
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        ReviewCreate::class,
        ReviewUpdate::class,
        ReviewPatch::class,
    ];

    public function __construct(
        RequestMapper $requestMapper,
    ) {
        parent::__construct($requestMapper);
    }
}
