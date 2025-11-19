<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Infrastructure\Service\ApiProxyService;
use App\User\Domain\Entity\User;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class UserConfigurationService
{
    private const string PATH = 'configuration';
    private const string CREATE_CONFIGURATION_PATH = 'api/v1/platform/configuration';

    public function __construct(
        private readonly ApiProxyService $proxyService,
        private readonly string $defaultWorkplaceId,
    ) {
    }

    /**
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function createDefaultConfiguration(User $user, ?string $token): void
    {
        if ($token === null) {
            return;
        }

        $payload = [
            'configurationKey' => 'theme',
            'userId' => $user->getId(),
            'configurationValue' => [
                'drawer' => true,
                'theme-primary' => '#23F80356',
                'background' => 'dark',
            ],
            'contextKey' => 'user',
            'contextId' => $user->getId(),
            'workplaceId' => $this->defaultWorkplaceId,
            'flags' => ['USER'],
        ];

        $payloadNotification = [
            'configurationKey' => 'notification',
            'userId' => $user->getId(),
            'configurationValue' => [
                'email.notification' => true,
                'push.notification' => true,
                'newsletter.notification' => true,
            ],
            'contextKey' => 'user',
            'contextId' => $user->getId(),
            'workplaceId' => $this->defaultWorkplaceId,
            'flags' => ['USER'],
        ];

        $this->proxyService->request(
            Request::METHOD_POST,
            self::PATH,
            $token,
            $payloadNotification,
            self::CREATE_CONFIGURATION_PATH
        );

        $this->proxyService->request(
            Request::METHOD_POST,
            self::PATH,
            $token,
            $payload,
            self::CREATE_CONFIGURATION_PATH
        );
    }
}
