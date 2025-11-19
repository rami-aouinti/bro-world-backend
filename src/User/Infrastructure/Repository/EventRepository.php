<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\Event as Entity;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @package App\Event
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
class EventRepository extends BaseRepository implements EventRepositoryInterface
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
     * @return array<int, Entity>
     */
    public function findEventsWithinPeriod(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this
            ->createQueryBuilder('event')
            ->where('event.start >= :from')
            ->andWhere('event.start < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }
}
