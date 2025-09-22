<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\User\Domain\Entity\Follow;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Repository\FollowRepository;
use App\User\Infrastructure\Repository\UserRepository;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Unit\User\Infrastructure\Repository
 */
final class FollowRepositoryTest extends KernelTestCase
{
    private FollowRepository $followRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = static::getContainer();
        $this->followRepository = $container->get(FollowRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    /**
     * @throws Throwable
     */
    public function testGetFollowStatusesMatchesLegacyCalculationWithSingleQuery(): void
    {
        $currentUser = $this->getUserByUsername('john');
        $mutualFriend = $this->getUserByUsername('john-logged');
        $followedOnly = $this->getUserByUsername('john-api');
        $followerOnly = $this->getUserByUsername('john-admin');
        $noRelation = $this->getUserByUsername('john-root');

        $this->createFollow($currentUser, $mutualFriend);
        $this->createFollow($mutualFriend, $currentUser);
        $this->createFollow($currentUser, $followedOnly);
        $this->createFollow($followerOnly, $currentUser);

        $allUsers = $this->userRepository->findAll();

        $expectedStatuses = [];

        foreach ($allUsers as $user) {
            if ($user === $currentUser) {
                continue;
            }

            $iFollowHim = $this->followRepository->findOneBy([
                'follower' => $currentUser,
                'followed' => $user,
            ]) !== null;

            $heFollowsMe = $this->followRepository->findOneBy([
                'follower' => $user,
                'followed' => $currentUser,
            ]) !== null;

            if ($iFollowHim && $heFollowsMe) {
                $status = 1;
            } elseif ($iFollowHim) {
                $status = 2;
            } elseif ($heFollowsMe) {
                $status = 3;
            } else {
                $status = 0;
            }

            $expectedStatuses[$user->getId()] = $status;
        }

        $debugStack = new DebugStack();
        $connection = $this->entityManager->getConnection();
        $configuration = $connection->getConfiguration();
        $previousLogger = $configuration->getSQLLogger();
        $configuration->setSQLLogger($debugStack);

        $actualStatuses = $this->followRepository->getFollowStatuses($currentUser);

        $configuration->setSQLLogger($previousLogger);

        $normalizedActual = [];
        foreach ($allUsers as $user) {
            if ($user === $currentUser) {
                continue;
            }

            $normalizedActual[$user->getId()] = $actualStatuses[$user->getId()] ?? 0;
        }

        self::assertSame($expectedStatuses, $normalizedActual);
        self::assertCount(1, $debugStack->queries);
        self::assertArrayHasKey($noRelation->getId(), $normalizedActual);
        self::assertSame(1, $normalizedActual[$mutualFriend->getId()]);
        self::assertSame(2, $normalizedActual[$followedOnly->getId()]);
        self::assertSame(3, $normalizedActual[$followerOnly->getId()]);
        self::assertSame(0, $normalizedActual[$noRelation->getId()]);
    }

    /**
     * @throws Throwable
     */
    private function createFollow(User $follower, User $followed): void
    {
        $follow = new Follow($follower, $followed);
        $this->entityManager->persist($follow);
        $this->entityManager->flush();
    }

    private function getUserByUsername(string $username): User
    {
        $user = $this->userRepository->findOneBy([
            'username' => $username,
        ]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
