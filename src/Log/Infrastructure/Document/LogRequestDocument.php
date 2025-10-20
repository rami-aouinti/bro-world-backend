<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Document;

use App\General\Infrastructure\Document\AbstractDocument;
use App\Log\Domain\Entity\LogRequest as LogRequestEntity;
use App\Log\Infrastructure\Document\Repository\LogRequestDocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'log_request', repositoryClass: LogRequestDocumentRepository::class)]
class LogRequestDocument extends AbstractDocument
{
    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $userId = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $apiKeyId = null;

    #[ODM\Field(type: 'int')]
    private int $statusCode = 0;

    #[ODM\Field(type: 'int')]
    private int $responseContentLength = 0;

    #[ODM\Field(type: 'bool')]
    private bool $mainRequest = true;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $time;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ODM\Field(type: 'string')]
    private string $agent = '';

    #[ODM\Field(type: 'string')]
    private string $httpHost = '';

    #[ODM\Field(type: 'string')]
    private string $clientIp = '';

    /**
     * @var array<int|string, array<int, string|null>|string|null>
     */
    #[ODM\Field(type: 'hash')]
    private array $headers = [];

    /**
     * @var array<int|string, mixed>
     */
    #[ODM\Field(type: 'hash')]
    private array $parameters = [];

    #[ODM\Field(type: 'string')]
    private string $method = '';

    #[ODM\Field(type: 'string')]
    private string $scheme = '';

    #[ODM\Field(type: 'string')]
    private string $basePath = '';

    #[ODM\Field(type: 'string')]
    private string $script = '';

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $path = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $queryString = null;

    #[ODM\Field(type: 'string')]
    private string $uri = '';

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $controller = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $contentType = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $contentTypeShort = null;

    #[ODM\Field(type: 'bool')]
    private bool $xmlHttpRequest = false;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $action = null;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $content = null;

    public static function fromEntity(LogRequestEntity $entity): self
    {
        $document = new self($entity->getId());
        $document->userId = $entity->getUser()?->getId();
        $document->apiKeyId = $entity->getApiKey()?->getId();
        $document->statusCode = $entity->getStatusCode();
        $document->responseContentLength = $entity->getResponseContentLength();
        $document->mainRequest = $entity->isMainRequest();
        $document->time = $entity->getTime();
        $document->date = $entity->getDate();
        $document->agent = $entity->getAgent();
        $document->httpHost = $entity->getHttpHost();
        $document->clientIp = $entity->getClientIp();
        $document->headers = $entity->getHeaders();
        $document->parameters = $entity->getParameters();
        $document->method = $entity->getMethod();
        $document->scheme = $entity->getScheme();
        $document->basePath = $entity->getBasePath();
        $document->script = $entity->getScript();
        $document->path = $entity->getPath();
        $document->queryString = $entity->getQueryString();
        $document->uri = $entity->getUri();
        $document->controller = $entity->getController();
        $document->contentType = $entity->getContentType();
        $document->contentTypeShort = $entity->getContentTypeShort();
        $document->xmlHttpRequest = $entity->isXmlHttpRequest();
        $document->action = $entity->getAction();
        $document->content = $entity->getContent();
        $document->setCreatedAt($entity->getTime());

        return $document;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getApiKeyId(): ?string
    {
        return $this->apiKeyId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseContentLength(): int
    {
        return $this->responseContentLength;
    }

    public function isMainRequest(): bool
    {
        return $this->mainRequest;
    }

    public function getTime(): \DateTimeImmutable
    {
        return $this->time;
    }

    public function getDate(): \DateTimeImmutable
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

    /**
     * @return array<int|string, array<int, string|null>|string|null>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getContentTypeShort(): ?string
    {
        return $this->contentTypeShort;
    }

    public function isXmlHttpRequest(): bool
    {
        return $this->xmlHttpRequest;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }
}
