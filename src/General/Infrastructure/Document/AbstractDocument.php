<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Document;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Uid\Uuid;

#[ODM\MappedSuperclass]
abstract class AbstractDocument
{
    #[ODM\Id(strategy: 'NONE', type: 'uuid')]
    protected string $id;

    #[ODM\Field(type: 'date_immutable')]
    protected DateTimeImmutable $createdAt;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? Uuid::v4()->toRfc4122();
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
}
