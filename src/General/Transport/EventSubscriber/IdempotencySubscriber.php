<?php

declare(strict_types=1);

namespace App\General\Transport\EventSubscriber;

use App\General\Application\Service\Http\IdempotencyService;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @package App\General
 */
class IdempotencySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly IdempotencyService $service)
    {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            KernelEvents::RESPONSE => ['onKernelResponse', -20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->service->supports($request)) {
            return;
        }

        $response = $this->service->getStoredResponse($request);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->service->supports($request)) {
            return;
        }

        $this->service->storeResponse($request, $event->getResponse());
    }
}
