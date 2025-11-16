<?php

declare(strict_types=1);

namespace App\User\Transport\EventSubscriber;

use App\User\Application\Service\UserConfigurationService;
use App\User\Domain\Event\UserConfigurationEvent;
use JsonException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsEventListener(event: UserConfigurationEvent::class)]
final class UserConfigurationSubscriber
{
    public function __construct(private readonly UserConfigurationService $configurationService)
    {
    }

    /**
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function __invoke(UserConfigurationEvent $event): void
    {
        $this->configurationService->createDefaultConfiguration($event->getUser(), $event->getToken());
    }
}
