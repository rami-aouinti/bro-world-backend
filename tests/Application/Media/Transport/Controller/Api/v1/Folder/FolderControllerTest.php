<?php

declare(strict_types=1);

namespace App\Tests\Application\Media\Transport\Controller\Api\v1\Folder;

use App\General\Domain\Utils\JSON;
use App\Media\Domain\Entity\Folder as FolderEntity;
use App\Media\Infrastructure\Repository\FolderRepository;
use App\Tests\TestCase\WebTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Tests
 */
class FolderControllerTest extends WebTestCase
{
    private static string $baseUrl = self::API_URL_PREFIX . '/v1/folder';

    #[TestDox('Test that `GET /api/v1/folder` request requires authentication.')]
    public function testThatGetFolderCollectionRequiresAuthentication(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', self::$baseUrl);
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), "Response:\n" . $response);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /api/v1/folder` returns data for an authenticated user.')]
    public function testThatGetFolderCollectionReturnsDataForAuthenticatedUser(): void
    {
        $this->createFolder('Existing folder');

        $client = $this->getTestClient('john-root', 'password-root');
        $client->request('GET', self::$baseUrl);
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertIsArray($responseData);
        self::assertNotEmpty($responseData);
        self::assertIsArray($responseData[0]);
        self::assertArrayHasKey('id', $responseData[0]);
        self::assertArrayHasKey('name', $responseData[0]);
    }

    #[TestDox('Test that `POST /api/v1/folder` request requires authentication.')]
    public function testThatCreateFolderRequiresAuthentication(): void
    {
        $client = $this->getTestClient();

        $client->request('POST', self::$baseUrl, content: JSON::encode(['name' => 'New folder']));
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), "Response:\n" . $response);
    }

    #[TestDox('Test that `POST /api/v1/folder` creates a folder for the authenticated user.')]
    public function testThatCreateFolderReturnsFolderDataForAuthenticatedUser(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');

        $requestData = [
            'name' => 'API created folder',
            'isPrivate' => false,
            'isFavorite' => true,
        ];

        $client->request('POST', self::$baseUrl, content: JSON::encode($requestData));
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertArrayHasKey('id', $responseData);
        self::assertSame($requestData['name'], $responseData['name']);
        self::assertSame($requestData['isFavorite'], $responseData['isFavorite']);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `PUT /api/v1/folder/{id}` updates folder data for the authenticated user.')]
    public function testThatUpdateFolderUpdatesEntity(): void
    {
        $folder = $this->createFolder('Folder to update');

        $client = $this->getTestClient('john-root', 'password-root');

        $client->request(
            method: 'PUT',
            uri: self::$baseUrl . '/' . $folder->getId(),
            content: JSON::encode(['name' => 'Updated folder name'])
        );
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);

        $responseData = JSON::decode($content, true);
        self::assertSame('Updated folder name', $responseData['name']);

        $updatedFolder = $this->getFolderRepository()->find($folder->getId());
        self::assertNotNull($updatedFolder);
        self::assertSame('Updated folder name', $updatedFolder->getName());
    }

    /**
     * @throws Throwable
     */
    private function createFolder(string $name): FolderEntity
    {
        self::bootKernel();

        $folder = new FolderEntity();
        $folder->setName($name);
        $folder->setUser($this->getRootUser());

        $repository = $this->getFolderRepository();
        $repository->save($folder);

        return $folder;
    }

    private function getFolderRepository(): FolderRepository
    {
        self::bootKernel();
        /** @var FolderRepository $repository */
        $repository = self::getContainer()->get(FolderRepository::class);

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
