<?php

declare(strict_types=1);

namespace App\User\Transport\AutoMapper\Plugin;

use App\General\Transport\AutoMapper\RestRequestMapper;

/**
 * @package App\Plugin
 */
class RequestMapper extends RestRequestMapper
{
    protected static array $properties = [
        'key',
        'name',
        'subTitle',
        'description',
        'logo',
        'icon',
        'installed',
        'link',
        'pricing',
        'action',
    ];
}
