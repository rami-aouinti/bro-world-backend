<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\Follow;
use App\User\Domain\Entity\Story as Entity;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\StoryRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

/**
 * @package App\Story
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null, ?string $entityManagerName = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null, string|null $entityManagerName = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?string $entityManagerName = null)
 * @method Entity[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?string $entityManagerName = null)
 * @method Entity[] findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null, ?string $entityManagerName = null)
 * @method Entity[] findAll(?string $entityManagerName = null)
 *
 * @codingStandardsIgnoreEnd
 */
class StoryRepository extends BaseRepository implements StoryRepositoryInterface
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function availableStories(User $user): array
    {
        $relatedUsers = [];

        foreach ($user->getFollowers() as $follower) {
            $relatedUsers[] = $follower->getFollower();
        }

        foreach ($user->getFollowings() as $following) {
            $relatedUsers[] = $following->getFollowed();
        }

        $relatedUsers[] = $user;

        $uniqueUserIds = array_unique(array_map(
            fn(User $u) => $u->getId(),
            $relatedUsers
        ));

        $qb = $this->createQueryBuilder('s');

        $qb->where('s.expiresAt > :now')
            ->andWhere('s.user IN (:userIds)')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('userIds', $uniqueUserIds);

        /** @var Entity[] $stories */
        $stories = $qb->getQuery()->getResult();

        $grouped = [];

        foreach ($stories as $story) {
            $storyUser = $story->getUser();
            $userId = $storyUser->getId();

            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'userId' => $userId,
                    'username' => $storyUser->getUsername(),
                    'stories' => [],
                ];
            }

            $grouped[$userId]['stories'][] = [
                'id' => $story->getId(),
                'mediaPath' => $story->getMediaPath(),
                'expiresAt' => $story->getExpiresAt()->format('Y-m-d H:i:s'),
            ];
        }

        return array_values($grouped);
    }
}
