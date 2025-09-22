<?php

declare(strict_types=1);

namespace App\General\Transport\EventSubscriber;

use App\General\Transport\Rest\RequestHandler;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @package App\General
 */
final class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        RequestHandler::setAllowedTenants(array_keys($this->managerRegistry->getManagerNames()));
    }
}
