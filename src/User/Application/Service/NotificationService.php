<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\General\Infrastructure\Service\ApiProxyService;
use App\User\Domain\Entity\User;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class MediaService
 *
 * @package App\Blog\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class NotificationService
{
    private const string PATH = 'notification';
    private const string CREATE_NOTIFICATION_PATH = 'api/v1/platform/notifications';

    public function __construct(
        private ApiProxyService $proxyService
    ) {}

    /**
     * @param string|null $token
     * @param string|null $channel
     * @param User|null   $user
     * @param string|null $content
     * @param string|null $conversationId
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @return void
     */
    public function createNotification(
        ?string $token,
        ?string $channel,
        ?User $user,
        ?string $content,
        ?string $conversationId
    ): void
    {
        $notification = [
            'channel' => $channel,
            'scope' => 'INDIVIDUAL',
            'topic' => '/messages/' . $conversationId,
            'pushTitle' => $user?->getFirstName() . ' ' . $user?->getFirstName(),
            'pushSubtitle' => $user?->getProfile()?->getPhoto(),
            'pushContent' => $content,
            'scopeTarget' => '["' . $user?->getId() . '"]',
        ];

        $this->createPush($token, $notification);
    }

    /**
     * @param string|null $token
     * @param array       $data
     *
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function createPush(
        ?string $token,
        array $data
    ): void
    {
        $this->proxyService->request(
            Request::METHOD_POST,
            self::PATH,
            $token,
            $data,
            self::CREATE_NOTIFICATION_PATH
        );
    }

    /**
     * @param string|null $token
     * @param array       $data
     * @param User $user
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @return void
     */
    public function createEmail(
        ?string $token,
        array $data,
        User $user): void
    {
       $this->proxyService->request(
            Request::METHOD_POST,
            self::PATH,
           $token,
            [
                'channel' => 'EMAIL',
                'templateId' => $data['templateId'],
                'emailSenderName' => $data['emailSenderName'],
                'emailSenderEmail' => $data['emailSenderEmail'],
                'emailSubject' => $data['emailSubject'],
                'recipients' => $data['recipients'],
                'scope' => 'INDIVIDUAL',
                'scopeTarget' => [$user->getId()],
            ],
            self::CREATE_NOTIFICATION_PATH
        );
    }
}
