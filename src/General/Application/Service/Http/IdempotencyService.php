<?php

declare(strict_types=1);

namespace App\General\Application\Service\Http;

use App\General\Domain\Entity\IdempotencyKey;
use App\General\Domain\Repository\Interfaces\IdempotencyKeyRepositoryInterface;
use App\General\Transport\Rest\RequestHandler;
use DateInterval;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function hash;
use function in_array;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * @package App\General
 */
class IdempotencyService
{
    private const array IDEMPOTENT_METHODS = [
        Request::METHOD_POST,
        Request::METHOD_PATCH,
        Request::METHOD_PUT,
        Request::METHOD_DELETE,
    ];

    public const string HEADER_NAME = 'Idempotency-Key';

    private readonly DateInterval $ttlInterval;

    public function __construct(
        private readonly IdempotencyKeyRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly int $idempotencyTtlSeconds,
    ) {
        $this->ttlInterval = new DateInterval(sprintf('PT%dS', $this->idempotencyTtlSeconds));
    }

    public function supports(Request $request): bool
    {
        if (!in_array(strtoupper($request->getMethod()), self::IDEMPOTENT_METHODS, true)) {
            return false;
        }

        return $request->headers->has(self::HEADER_NAME);
    }

    public function getStoredResponse(Request $request): ?Response
    {
        $key = $this->getKey($request);

        if ($key === null) {
            return null;
        }

        $tenant = RequestHandler::getTenant($request);
        $entity = $this->repository->findOneByKey($key, $tenant);

        if ($entity === null) {
            return null;
        }

        $this->assertSamePayload($entity, $request, $tenant);

        if ($entity->isExpired()) {
            $this->repository->remove($entity, true, $tenant);

            return null;
        }

        $response = $entity->toResponse();
        $response->headers->set(self::HEADER_NAME, $entity->getKey());
        $response->headers->set('X-Idempotent-Replay', 'true');

        return $response;
    }

    /**
     * @throws BadRequestHttpException
     */
    public function storeResponse(Request $request, Response $response): void
    {
        $key = $this->getKey($request);

        if ($key === null) {
            return;
        }

        if ($response->isServerError()) {
            $this->logger->debug('Skip idempotency persistence for server error response.', [
                'status' => $response->getStatusCode(),
            ]);

            return;
        }

        $hash = $this->hashRequest($request);
        $tenant = RequestHandler::getTenant($request);
        $entity = $this->repository->findOneByKey($key, $tenant);

        if ($entity !== null) {
            $this->assertSamePayload($entity, $request, $tenant);
            $response->headers->set(self::HEADER_NAME, $entity->getKey());

            return;
        }

        $entity = new IdempotencyKey($key, $hash);
        $entity->setResponseStatus($response->getStatusCode());
        $entity->setResponseBody($response->getContent() ?? '');
        $entity->setResponseHeaders($response->headers->allPreserveCaseWithoutCookies());
        $entity->setTenant($tenant);
        $entity->extendExpiry($this->ttlInterval);

        try {
            $this->repository->save($entity, true, $tenant);
        } catch (UniqueConstraintViolationException) {
            $this->logger->info('Concurrent idempotency insert detected, ignoring duplicate.', [
                'key' => $key,
            ]);
        }

        $response->headers->set(self::HEADER_NAME, $entity->getKey());
        $response->headers->set('X-Idempotent-Replay', 'false');
    }

    private function assertSamePayload(IdempotencyKey $entity, Request $request, ?string $tenant): void
    {
        $hash = $this->hashRequest($request);

        if ($entity->getRequestHash() !== $hash) {
            $this->logger->warning('Idempotency key reuse with different payload detected.', [
                'key' => $entity->getKey(),
                'tenant' => $tenant,
            ]);

            throw new BadRequestHttpException('Idempotency key already used with a different payload.');
        }
    }

    private function getKey(Request $request): ?string
    {
        $headerValue = $request->headers->get(self::HEADER_NAME);

        if ($headerValue === null) {
            return null;
        }

        $key = trim($headerValue);

        return $key === '' ? null : $key;
    }

    private function hashRequest(Request $request): string
    {
        $payload = $request->getContent();
        $uri = sprintf('%s|%s', strtoupper($request->getMethod()), $request->getRequestUri());

        return hash('sha256', $uri . '|' . $payload);
    }
}
