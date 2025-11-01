<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Document;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Ramsey\Uuid\UuidInterface;
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
    public function __construct()
    {
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
