<?php

declare(strict_types=1);

namespace App\Log\Application\Service;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @package App\Log
 */
class CorrelationIdProvider
{
    public const string ATTRIBUTE = '_correlation_id';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getCorrelationId(): ?string
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return null;
        }

        $correlationId = $request->attributes->get(self::ATTRIBUTE);

        return $correlationId !== null ? (string)$correlationId : null;
    }

    public function ensureCorrelationId(?string $incoming = null): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $correlationId = $incoming !== null && $incoming !== '' ? $incoming : Uuid::uuid4()->toString();

        if ($request !== null) {
            $request->attributes->set(self::ATTRIBUTE, $correlationId);
        }

        return $correlationId;
    }
}
