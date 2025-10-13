<?php

declare(strict_types=1);

namespace App\General\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * @package App\General
 */
#[ORM\Entity]
#[ORM\Table(name: 'idempotency_key')]
#[ORM\UniqueConstraint(name: 'uq_idempotency_key_key', columns: ['idempotency_key'])]
#[ORM\Index(name: 'idx_idempotency_key_expires_at', columns: ['expires_at'])]
class IdempotencyKey implements EntityInterface
{
    use Uuid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[Groups(['IdempotencyKey', 'IdempotencyKey.id'])]
    private UuidInterface $id;

    #[ORM\Column(name: 'idempotency_key', type: Types::STRING, length: 255, unique: true)]
    #[Groups(['IdempotencyKey', 'IdempotencyKey.key'])]
    private string $key;

    #[ORM\Column(name: 'request_hash', type: Types::STRING, length: 128)]
    #[Groups(['IdempotencyKey', 'IdempotencyKey.requestHash'])]
    private string $requestHash;

    #[ORM\Column(name: 'response_status', type: Types::SMALLINT)]
    #[Groups(['IdempotencyKey', 'IdempotencyKey.responseStatus'])]
    private int $responseStatus;

    /**
     * @var array<string, array<int, string>>
     */
    #[ORM\Column(name: 'response_headers', type: Types::JSON, options: ['jsonb' => true])]
    private array $responseHeaders = [];

    #[ORM\Column(name: 'response_body', type: Types::TEXT)]
    private string $responseBody;

    #[ORM\Column(name: 'tenant', type: Types::STRING, length: 64, nullable: true)]
    private ?string $tenant = null;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    /**
     * @throws Throwable
     */
    public function __construct(string $key, string $requestHash)
    {
        $this->id = $this->createUuid();
        $this->key = $key;
        $this->requestHash = $requestHash;
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(int $status): void
    {
        $this->responseStatus = $status;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @param array<string, array<int, string>> $headers
     */
    public function setResponseHeaders(array $headers): void
    {
        $this->responseHeaders = $headers;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function setResponseBody(string $body): void
    {
        $this->responseBody = $body;
    }

    public function getTenant(): ?string
    {
        return $this->tenant;
    }

    public function setTenant(?string $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isExpired(?DateTimeImmutable $reference = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $reference ??= new DateTimeImmutable();

        return $this->expiresAt <= $reference;
    }

    public function extendExpiry(DateInterval $interval): void
    {
        $base = $this->expiresAt ?? new DateTimeImmutable();
        $this->expiresAt = $base->add($interval);
    }

    public function toResponse(): Response
    {
        $response = new Response($this->responseBody, $this->responseStatus);

        foreach ($this->responseHeaders as $name => $values) {
            foreach ($values as $value) {
                $response->headers->set($name, $value, false);
            }
        }

        return $response;
    }
}
