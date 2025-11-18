<?php

declare(strict_types=1);

namespace App\Messenger\Application\Service;

use App\General\Application\Rest\Interfaces\RestResourceInterface;
use App\General\Transport\Rest\Interfaces\ResponseHandlerInterface;
use App\Messenger\Application\Service\Interfaces\ConversationMessageCacheServiceInterface;
use App\Messenger\Domain\Entity\Conversation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function serialize;
use function sha1;

/**
 * @package App\Messenger
 */
readonly class ConversationMessageCacheService implements ConversationMessageCacheServiceInterface
{
    private const int CACHE_TTL_SECONDS = 30;

    public function __construct(private CacheInterface $cache)
    {
    }

    public function createResponse(
        Request $request,
        Conversation $conversation,
        callable $dataProvider,
        RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ): Response {
        if (!$this->isCacheable($request)) {
            $data = $dataProvider();

            return $responseHandler->createResponse($request, $data, $resource);
        }

        $cacheKey = $this->getCacheKey($conversation, $request);
        $context = $responseHandler->getSerializeContext($request, $resource);

        $payload = $this->cache->get($cacheKey, function (ItemInterface $item) use ($dataProvider, $responseHandler, $context) {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);
            $messages = $dataProvider();

            return $responseHandler
                ->getSerializer()
                ->serialize($messages, ResponseHandlerInterface::FORMAT_JSON, $context);
        });

        return new JsonResponse($payload, Response::HTTP_OK, [], true);
    }

    public function invalidateConversation(Conversation $conversation): void
    {
        $this->cache->delete($this->getCacheKey($conversation));
    }

    private function getCacheKey(Conversation $conversation, ?Request $request = null): string
    {
        $context = $request !== null ? $request->query->all() : [];
        $contextHash = sha1(serialize($context));

        return 'messenger_conversation_messages_' . $conversation->getId() . '_' . $contextHash;
    }

    private function isCacheable(Request $request): bool
    {
        if (!$request->isMethodCacheable()) {
            return false;
        }

        return $request->query->count() === 0;
    }
}
