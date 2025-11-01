<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Document;

use App\General\Domain\Rest\UuidHelper;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Ramsey\Uuid\UuidInterface;

use function is_string;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @package App\General
 *
 *
 */
#[ODM\MappedSuperclass]
abstract class AbstractDocument
{
    #[ODM\Id(type: 'uuid', strategy: 'NONE')]
    protected UuidInterface $id;

    #[ODM\Field(type: 'date_immutable')]
    protected DateTimeImmutable $createdAt;

    /**
     * @throws DateMalformedStringException
     */
    public function __construct(string|UuidInterface $id)
    {
        if (is_string($id)) {
            $id = UuidHelper::fromString($id);
        }

        $this->id = $id;
        $this->createdAt = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    public function getId(): UuidInterface
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
}
