<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\Follow as Entity;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\Interfaces\FollowRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @package App\Follow
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
class FollowRepository extends BaseRepository implements FollowRepositoryInterface
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
     * @return array<string, int>
     */
    public function getFollowStatuses(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('follow')
            ->addSelect('follower', 'followed')
            ->join('follow.follower', 'follower')
            ->join('follow.followed', 'followed')
            ->where('follow.follower = :user OR follow.followed = :user')
            ->setParameter('user', $user);

        $relations = $queryBuilder
            ->getQuery()
            ->getResult();

        $statuses = [];
        $userId = $user->getId();

        foreach ($relations as $relation) {
            if (!$relation instanceof Entity) {
                continue;
            }

            $followerId = $relation->getFollower()->getId();
            $followedId = $relation->getFollowed()->getId();

            if ($followerId === $userId) {
                $statuses[$followedId] = ($statuses[$followedId] ?? 0) | 0b10;
            }

            if ($followedId === $userId) {
                $statuses[$followerId] = ($statuses[$followerId] ?? 0) | 0b01;
            }
        }

        foreach ($statuses as $otherUserId => $mask) {
            $statuses[$otherUserId] = match ($mask) {
                0b11 => 1,
                0b10 => 2,
                0b01 => 3,
                default => 0,
            };
        }

        return $statuses;
    }
}
