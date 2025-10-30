<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Document;

use App\General\Domain\Rest\UuidHelper;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface as RamseyUuidInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[ODM\MappedSuperclass]
abstract class AbstractDocument
{
    #[ODM\Id(strategy: 'NONE', type: 'uuid')]
    protected string $id;

    #[ODM\Field(type: 'date_immutable')]
    protected DateTimeImmutable $createdAt;

    public function __construct(null|string|RamseyUuidInterface $id = null)
    {
        $this->id = $this->resolveIdentifier($id);
        $this->createdAt = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    protected function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    private function resolveIdentifier(null|string|RamseyUuidInterface $id): string
    {
        if ($id instanceof RamseyUuidInterface) {
            return Uuid::fromBinary($id->getBytes())->toRfc4122();
        }

        if ($id !== null) {
            try {
                return Uuid::fromString($id)->toRfc4122();
            } catch (InvalidArgumentException) {
                try {
                    return Uuid::fromBinary(UuidHelper::getBytes($id))->toRfc4122();
                } catch (Throwable $exception) {
                    throw new InvalidArgumentException('Invalid UUID string provided for document identifier.', previous: $exception);
                }
            }
        }

        return Uuid::v4()->toRfc4122();
    }
}
