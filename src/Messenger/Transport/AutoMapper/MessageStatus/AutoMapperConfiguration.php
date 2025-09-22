<?php

declare(strict_types=1);

namespace App\Messenger\Transport\AutoMapper\MessageStatus;

use App\General\Transport\AutoMapper\RestAutoMapperConfiguration;
use App\Messenger\Application\DTO\MessageStatus\MessageStatus;

/**
 * @package App\Messenger
 */
class AutoMapperConfiguration extends RestAutoMapperConfiguration
{
    /**
     * @var array<int, class-string>
     */
    protected static array $requestMapperClasses = [
        MessageStatus::class,
    ];

    public function __construct(
        RequestMapper $requestMapper,
    ) {
        parent::__construct($requestMapper);
    }
}
