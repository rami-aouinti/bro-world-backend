<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\General\Domain\Service\Interfaces\ElasticsearchServiceInterface;
use App\User\Application\Service\UserElasticsearchService;
use App\User\Domain\Entity\Story;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use App\User\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * @package App\Tests\Unit\User\Application\Service
 */
final class UserElasticsearchServiceTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testUpdateReindexesAllUsersWithDistinctDocuments(): void
    {
        $elasticsearchService = $this->createMock(ElasticsearchServiceInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $followRepository = $this->createMock(FollowRepositoryInterface::class);

        $service = new UserElasticsearchService(
            $elasticsearchService,
            $userRepository,
            $followRepository
        );

        $firstUser = $this->createUser('11111111-1111-1111-1111-111111111111', 'alice');
        $secondUser = $this->createUser('22222222-2222-2222-2222-222222222222', 'bob');

        $firstStory = new Story($firstUser, 'media/alice-story.mp4');
        $firstUser->getStories()->add($firstStory);

        $users = [$firstUser, $secondUser];

        $userRepository->expects(self::once())
            ->method('findAll')
            ->willReturn($users);

        $followRepository->expects(self::exactly(2))
            ->method('getFollowStatuses')
            ->withConsecutive([
                self::identicalTo($firstUser),
            ], [
                self::identicalTo($secondUser),
            ])
            ->willReturnOnConsecutiveCalls(
                [$secondUser->getId() => 1],
                [$firstUser->getId() => 2]
            );

        $elasticsearchService->expects(self::once())
            ->method('delete')
            ->with('users');

        $indexedDocuments = [];
        $elasticsearchService->expects(self::exactly(2))
            ->method('index')
            ->willReturnCallback(static function (string $index, string $documentId, array $body) use (&$indexedDocuments): void {
                TestCase::assertSame('users', $index);
                $indexedDocuments[$documentId] = $body;
            });

        $service->updateUserInElasticsearch($firstUser);

        self::assertSame([
            $firstUser->getId(),
            $secondUser->getId(),
        ], array_keys($indexedDocuments));

        $firstDocument = $indexedDocuments[$firstUser->getId()];
        $secondDocument = $indexedDocuments[$secondUser->getId()];

        self::assertNotSame($firstDocument, $secondDocument);

        self::assertSame($firstUser->getId(), $firstDocument['id']);
        self::assertSame('alice', $firstDocument['username']);
        self::assertCount(1, $firstDocument['stories']);
        self::assertSame($firstStory->getId(), $firstDocument['stories'][0]['id']);
        self::assertSame('media/alice-story.mp4', $firstDocument['stories'][0]['mediaPath']);

        self::assertSame($secondUser->getId(), $secondDocument['id']);
        self::assertSame('bob', $secondDocument['username']);
        self::assertCount(0, $secondDocument['stories']);

        self::assertSame([
            ['id' => $secondUser->getId(), 'status' => 1],
        ], $firstDocument['friends']);
        self::assertSame([
            ['id' => $firstUser->getId(), 'status' => 2],
        ], $secondDocument['friends']);

        foreach ($firstDocument['friends'] as $friend) {
            self::assertSame(['id', 'status'], array_keys($friend));
            self::assertIsString($friend['id']);
            self::assertIsInt($friend['status']);
        }

        foreach ($secondDocument['friends'] as $friend) {
            self::assertSame(['id', 'status'], array_keys($friend));
            self::assertIsString($friend['id']);
            self::assertIsInt($friend['status']);
        }
    }

    /**
     * @throws Throwable
     */
    private function createUser(string $id, string $username): User
    {
        $user = new User();
        $user->setId(Uuid::fromString($id));
        $user->setUsername($username);
        $user->setFirstName(ucfirst($username));
        $user->setLastName('User');
        $user->setEmail(sprintf('%s@example.com', $username));
        $user->setEnabled(true);

        return $user;
    }
}
