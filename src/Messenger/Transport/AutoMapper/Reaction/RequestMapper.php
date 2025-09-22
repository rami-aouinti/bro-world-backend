<?php

declare(strict_types=1);

namespace App\Messenger\Transport\AutoMapper\Reaction;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\Messenger\Application\Resource\MessageResource;
use App\Messenger\Domain\Entity\Message;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;

/**
 * @package App\Messenger
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'message',
        'user',
        'emoji',
    ];

    public function __construct(
        private readonly MessageResource $messageResource,
        private readonly UserResource $userResource,
    ) {
    }

    protected function transformMessage(string $message): Message
    {
        return $this->messageResource->getReference($message);
    }

    protected function transformUser(string $user): User
    {
        return $this->userResource->getReference($user);
    }
}
