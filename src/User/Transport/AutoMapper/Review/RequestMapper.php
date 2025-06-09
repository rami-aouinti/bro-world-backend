<?php

declare(strict_types=1);

namespace App\User\Transport\AutoMapper\Review;

use App\General\Transport\AutoMapper\RestRequestMapper;

/**
 * @package App\Review
 */
class RequestMapper extends RestRequestMapper
{
    protected static array $properties = [
        'rating',
        'comment'
    ];
}
