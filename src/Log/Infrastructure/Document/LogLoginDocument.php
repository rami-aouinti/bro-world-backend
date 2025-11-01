<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Log\Domain\Entity\LogLogin as LogLoginEntity;
use App\Log\Infrastructure\Document\Repository\LogLoginDocumentRepository;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @package App\Log
 */
#[ODM\Document(collection: 'log_login', repositoryClass: LogLoginDocumentRepository::class)]
class LogLoginDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string')]
    private string $type = '';

    #[ODM\Field(type: 'string')]
    private string $username = '';

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $userId = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $clientType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $clientName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $clientShortName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $clientVersion = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $clientEngine = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $osName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $osShortName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $osVersion = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $osPlatform = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $deviceName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $brandName = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $model = null;

    #[ODM\Field(type: 'date_immutable')]
    private DateTimeImmutable $time;

    #[ODM\Field(type: 'date_immutable')]
    private DateTimeImmutable $date;

    #[ODM\Field(type: 'string')]
    private string $agent = '';

    #[ODM\Field(type: 'string')]
    private string $httpHost = '';

    #[ODM\Field(type: 'string')]
    private string $clientIp = '';

    /**
     * @param LogLoginEntity $entity
     *
     * @return LogLoginDocument
     */
    public static function fromEntity(LogLoginEntity $entity): self
    {
        $document = new self($entity->getId());
        $document->type = $entity->getType()->value;
        $document->username = $entity->getUsername();
        $document->userId = $entity->getUser()?->getId();
        $document->clientType = $entity->getClientType();
        $document->clientName = $entity->getClientName();
        $document->clientShortName = $entity->getClientShortName();
        $document->clientVersion = $entity->getClientVersion();
        $document->clientEngine = $entity->getClientEngine();
        $document->osName = $entity->getOsName();
        $document->osShortName = $entity->getOsShortName();
        $document->osVersion = $entity->getOsVersion();
        $document->osPlatform = $entity->getOsPlatform();
        $document->deviceName = $entity->getDeviceName();
        $document->brandName = $entity->getBrandName();
        $document->model = $entity->getModel();
        $document->time = $entity->getTime();
        $document->date = $entity->getDate();
        $document->agent = $entity->getAgent();
        $document->httpHost = $entity->getHttpHost();
        $document->clientIp = $entity->getClientIp();
        $document->setCreatedAt($entity->getTime());

        return $document;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function getClientShortName(): ?string
    {
        return $this->clientShortName;
    }

    public function getClientVersion(): ?string
    {
        return $this->clientVersion;
    }

    public function getClientEngine(): ?string
    {
        return $this->clientEngine;
    }

    public function getOsName(): ?string
    {
        return $this->osName;
    }

    public function getOsShortName(): ?string
    {
        return $this->osShortName;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function getOsPlatform(): ?string
    {
        return $this->osPlatform;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getAgent(): string
    {
        return $this->agent;
    }

    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }
}
