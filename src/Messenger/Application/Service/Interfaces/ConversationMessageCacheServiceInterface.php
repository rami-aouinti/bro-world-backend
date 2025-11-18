<?php

declare(strict_types=1);

namespace App\Messenger\Application\Service\Interfaces;

use App\General\Application\Rest\Interfaces\RestResourceInterface;
use App\General\Transport\Rest\Interfaces\ResponseHandlerInterface;
use App\Messenger\Domain\Entity\Conversation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package App\Messenger
 */
interface ConversationMessageCacheServiceInterface
{
    /**
     * @param callable(): array<int, object> $dataProvider
     */
    public function createResponse(
        Request $request,
        Conversation $conversation,
        callable $dataProvider,
        RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ): Response;

    public function invalidateConversation(Conversation $conversation): void;
}
