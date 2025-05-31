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
        $qb = $this->createQueryBuilder('s');

        $qb->join(Follow::class, 'f', 'WITH', 'f.followed = s.user')
            ->where('f.follower = :user')
            ->andWhere('s.expiresAt > :now')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameters([
                'user' => $user,
                'now' => new \DateTimeImmutable(),
            ]);

        $stories = $qb->getQuery()->getResult();

        return array_map(fn(Entity $s) => [
            'id' => $s->getId(),
            'mediaPath' => $s->getMediaPath(),
            'user' => $s->getUser()->getUsername(),
            'expiresAt' => $s->getExpiresAt()->format('Y-m-d H:i:s'),
        ], $stories);
    }
}
