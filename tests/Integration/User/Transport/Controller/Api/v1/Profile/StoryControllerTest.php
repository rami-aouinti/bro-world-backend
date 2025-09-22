<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Transport\Controller\Api\v1\Profile;

use App\Tests\TestCase\WebTestCase;
use App\User\Application\Service\UserService;
use App\User\Domain\Exception\StoryUploadException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function file_put_contents;
use function json_decode;
use function sys_get_temp_dir;
use function tempnam;

final class StoryControllerTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    public function testUploadStoryWithoutFileReturnsBadRequest(): void
    {
        $client = $this->getTestClient('john', 'password', server: ['CONTENT_TYPE' => 'multipart/form-data']);

        $client->request('POST', self::API_URL_PREFIX . '/v1/story', [], [], ['CONTENT_TYPE' => 'multipart/form-data']);

        $response = $client->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('No file provided for story upload.', $data['error']);
    }

    /**
     * @throws Throwable
     */
    public function testUploadStoryMoveFailureReturnsServerError(): void
    {
        $container = static::getContainer();
        $originalService = $container->get(UserService::class);

        /** @var UserService&MockObject $mockService */
        $mockService = $this->createMock(UserService::class);
        $mockService->method('uploadStory')->willThrowException(StoryUploadException::moveFailed());

        $container->set(UserService::class, $mockService);

        try {
            $client = $this->getTestClient('john', 'password', server: ['CONTENT_TYPE' => 'multipart/form-data']);

            $tempFile = tempnam(sys_get_temp_dir(), 'story');
            file_put_contents($tempFile, 'content');
            $uploadedFile = new UploadedFile($tempFile, 'story.txt', 'text/plain', null, true);

            $client->request(
                'POST',
                self::API_URL_PREFIX . '/v1/story',
                [],
                ['file' => $uploadedFile],
                ['CONTENT_TYPE' => 'multipart/form-data']
            );

            $response = $client->getResponse();

            self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

            $data = json_decode((string)$response->getContent(), true);
            self::assertIsArray($data);
            self::assertArrayHasKey('error', $data);
            self::assertSame('Failed to store story file.', $data['error']);
        } finally {
            $container->set(UserService::class, $originalService);
        }
    }
}
