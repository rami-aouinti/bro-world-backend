<?php

declare(strict_types=1);

namespace App\General\Transport\EventSubscriber;

use App\Log\Application\Service\CorrelationIdProvider;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function trim;

/**
 * @package App\General
 */
class CorrelationIdSubscriber implements EventSubscriberInterface
{
    public const string HEADER_NAME = 'X-Correlation-ID';

    public function __construct(private readonly CorrelationIdProvider $provider)
    {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $headerValue = $request->headers->get(self::HEADER_NAME) ?? $request->headers->get('X-Correlation-Id');
        $correlationId = $headerValue !== null ? trim($headerValue) : null;

        $id = $this->provider->ensureCorrelationId($correlationId);
        $request->headers->set(self::HEADER_NAME, $id);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $correlationId = $this->provider->getCorrelationId();

        if ($correlationId !== null) {
            $event->getResponse()->headers->set(self::HEADER_NAME, $correlationId);
        }
    }
}
