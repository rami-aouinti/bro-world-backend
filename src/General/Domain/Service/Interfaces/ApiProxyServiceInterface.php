<?php

declare(strict_types=1);

namespace App\General\Domain\Service\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * @package App\General
 */
interface ApiProxyServiceInterface
{
    /**
     * @throws Throwable
     */
    public function request(string $method, string $type, ?string $token, array $body = [], string $path = ''): void;

    /**
     * @throws Throwable
     */
    public function requestFile(string $method, string $type, Request $request, array $body = [], string $path = ''): array;
}
