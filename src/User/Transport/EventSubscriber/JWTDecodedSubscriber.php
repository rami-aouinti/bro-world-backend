<?php

declare(strict_types=1);

namespace App\User\Transport\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function array_key_exists;
use function hash;
use function implode;

/**
 * @package App\User
 */
class JWTDecodedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            JWTDecodedEvent::class => 'onJWTDecoded',
            Events::JWT_DECODED => 'onJWTDecoded',
        ];
    }

    /**
     * Subscriber method to make some custom JWT payload checks.
     *
     * This method is called when 'lexik_jwt_authentication.on_jwt_decoded' event is broadcast.
     */
    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        // No need to continue event is invalid
        if (!$event->isValid()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $this->checkPayload($event, $request);

        if ($request === null) {
            $this->logger->error('Request not available');
            $event->markAsInvalid();
        }
    }

    /**
     * Method to check payload data.
     */
    private function checkPayload(JWTDecodedEvent $event, ?Request $request): void
    {
        if ($request === null) {
            return;
        }

        $payload = $event->getPayload();
        // Get bits for checksum calculation
        $bits = [
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        ];
        // Calculate checksum
        $checksum = hash('sha512', implode('|', $bits));

        // Custom checks to validate user's JWT
        //if (!array_key_exists('checksum', $payload) || $payload['checksum'] !== $checksum) {
        //    $event->markAsInvalid();
        //}
    }
}
