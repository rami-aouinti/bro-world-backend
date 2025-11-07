<?php

declare(strict_types=1);

namespace App\Workplace\Transport\AutoMapper\Workplace;

use App\General\Transport\AutoMapper\RestRequestMapper;

/**
 * @package App\User
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'name'
    ];
}
