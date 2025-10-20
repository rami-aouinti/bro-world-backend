<?php

declare(strict_types=1);

namespace App\Tests\Integration\Log;

use App\Log\Application\Resource\LogLoginFailureResource;
use App\Log\Application\Service\LoginLoggerService;
use App\Log\Application\Service\RequestLoggerService;
use App\Log\Domain\Entity\LogLoginFailure;
use App\Log\Domain\Enum\LogLogin;
use App\Log\Domain\Repository\Interfaces\LogLoginDocumentRepositoryInterface;
use App\Log\Domain\Repository\Interfaces\LogLoginFailureDocumentRepositoryInterface;
use App\Log\Domain\Repository\Interfaces\LogRequestDocumentRepositoryInterface;
use App\Tests\TestCase\WebTestCase;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class LogMongoReplicationTest extends WebTestCase
{
    private RequestLoggerService $requestLoggerService;
    private LoginLoggerService $loginLoggerService;
    private LogLoginFailureResource $logLoginFailureResource;
    private LogRequestDocumentRepositoryInterface $logRequestDocumentRepository;
    private LogLoginDocumentRepositoryInterface $logLoginDocumentRepository;
    private LogLoginFailureDocumentRepositoryInterface $logLoginFailureDocumentRepository;
    private RequestStack $requestStack;
    private UserResource $userResource;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->requestLoggerService = $container->get(RequestLoggerService::class);
        $this->loginLoggerService = $container->get(LoginLoggerService::class);
        $this->logLoginFailureResource = $container->get(LogLoginFailureResource::class);
        $this->logRequestDocumentRepository = $container->get(LogRequestDocumentRepositoryInterface::class);
        $this->logLoginDocumentRepository = $container->get(LogLoginDocumentRepositoryInterface::class);
        $this->logLoginFailureDocumentRepository = $container->get(LogLoginFailureDocumentRepositoryInterface::class);
        $this->requestStack = $container->get(RequestStack::class);
        $this->userResource = $container->get(UserResource::class);
    }

    public function testRequestLoggerPersistsDocument(): void
    {
        $before = count($this->logRequestDocumentRepository->findAll());

        $request = Request::create('/integration/request-log', Request::METHOD_GET, [], [], [], [
            'HTTP_USER_AGENT' => 'PHPUnit Request Logger',
        ]);
        $response = new HttpResponse('', HttpResponse::HTTP_OK);

        $this->requestLoggerService->setRequest($request);
        $this->requestLoggerService->setResponse($response);
        $this->requestLoggerService->setMainRequest(true);
        $this->requestLoggerService->handle();

        $after = count($this->logRequestDocumentRepository->findAll());
        self::assertSame($before + 1, $after);
    }

    /**
     * @throws Throwable
     */
    public function testLoginLoggerPersistsDocument(): void
    {
        $before = count($this->logLoginDocumentRepository->findAll());

        $user = $this->getUser('john-user');
        $request = Request::create('/login', Request::METHOD_POST, [], [], [], [
            'HTTP_USER_AGENT' => 'PHPUnit Login Logger',
        ]);
        $this->requestStack->push($request);

        $this->loginLoggerService->setUser($user);
        $this->loginLoggerService->process(LogLogin::LOGIN);

        $this->requestStack->pop();

        $after = count($this->logLoginDocumentRepository->findAll());
        self::assertSame($before + 1, $after);
    }

    /**
     * @throws Throwable
     */
    public function testLoginFailureResourcePersistsAndClearsDocuments(): void
    {
        $user = $this->getUser('john-user');
        $before = count($this->logLoginFailureDocumentRepository->findAll());

        $entity = new LogLoginFailure($user);
        $this->logLoginFailureResource->save($entity);

        $after = count($this->logLoginFailureDocumentRepository->findAll());
        self::assertSame($before + 1, $after);

        $this->logLoginFailureResource->reset($user);
        $remaining = $this->logLoginFailureDocumentRepository->findBy([
            'userId' => $user->getId(),
        ]);
        self::assertCount(0, $remaining);
    }

    private function getUser(string $username): User
    {
        $user = $this->userResource->findOneBy([
            'username' => $username,
        ]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
