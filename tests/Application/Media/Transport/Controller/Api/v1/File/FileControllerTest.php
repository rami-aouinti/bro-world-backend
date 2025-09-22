<?php

declare(strict_types=1);

namespace App\Tests\Application\Media\Transport\Controller\Api\v1\File;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\File as FileEntity;
use App\Media\Domain\Enum\FileType;
use App\Media\Infrastructure\Repository\FileRepository;
use App\Tests\TestCase\WebTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Tests
 */
class FileControllerTest extends WebTestCase
{
    private static string $baseUrl = self::API_URL_PREFIX . '/v1/file';

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /api/v1/file/{id}` request requires authentication.')]
    public function testThatGetFileRequiresAuthentication(): void
    {
        $file = $this->createFile();

        $client = $this->getTestClient();
        $client->request('GET', self::$baseUrl . '/' . $file->getId());
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /api/v1/file/{id}` returns file data for the authenticated user.')]
    public function testThatGetFileReturnsDataForAuthenticatedUser(): void
    {
        $file = $this->createFile();

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', self::$baseUrl . '/' . $file->getId());
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertArrayHasKey('id', $responseData);
        self::assertArrayHasKey('name', $responseData);
        self::assertArrayHasKey('url', $responseData);
        self::assertSame($file->getName(), $responseData['name']);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `DELETE /api/v1/file/{id}` removes the file for the authenticated user.')]
    public function testThatDeleteFileRemovesEntity(): void
    {
        $file = $this->createFile();

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('DELETE', self::$baseUrl . '/' . $file->getId());
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $deleted = $this->getFileRepository()->find($file->getId());
        self::assertNull($deleted);
    }

    /**
     * @throws Throwable
     */
    private function createFile(): FileEntity
    {
        self::bootKernel();

        $file = new FileEntity();
        $file->setName('Test file');
        $file->setUrl('https://example.com/file.pdf');
        $file->setSize(512);
        $file->setType(FileType::PDF);
        $file->setExtension('pdf');
        $file->setUser($this->getRootUser());
        $file->setIsPrivate(false);
        $file->setIsFavorite(false);

        $this->getFileRepository()->save($file);

        return $file;
    }

    private function getFileRepository(): FileRepository
    {
        self::bootKernel();
        /** @var FileRepository $repository */
        $repository = self::getContainer()->get(FileRepository::class);

        return $repository;
    }

    private function getRootUser(): User
    {
        self::bootKernel();
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'john-root']);
        self::assertNotNull($user);

        return $user;
    }
}
