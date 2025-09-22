<?php

declare(strict_types=1);

namespace App\Messenger\Transport\AutoMapper\Message;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\Messenger\Application\Resource\ConversationResource;
use App\Messenger\Application\Resource\MessageResource;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message as MessageEntity;
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
        'conversation',
        'sender',
        'text',
        'mediaUrl',
        'mediaType',
        'attachmentUrl',
        'attachmentType',
        'replyTo',
    ];

    public function __construct(
        private readonly ConversationResource $conversationResource,
        private readonly UserResource $userResource,
        private readonly MessageResource $messageResource,
    ) {
    }

    protected function transformConversation(string $conversation): Conversation
    {
        return $this->conversationResource->getReference($conversation);
    }

    protected function transformSender(string $sender): User
    {
        return $this->userResource->getReference($sender);
    }

    protected function transformReplyTo(?string $replyTo): ?MessageEntity
    {
        return $replyTo !== null ? $this->messageResource->getReference($replyTo) : null;
    }
}
