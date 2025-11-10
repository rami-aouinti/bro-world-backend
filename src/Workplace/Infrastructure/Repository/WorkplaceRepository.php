<?php

declare(strict_types=1);

namespace App\Workplace\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\User;
use App\Workplace\Domain\Entity\Workplace as Entity;
use App\Workplace\Domain\Repository\Interfaces\WorkplaceRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

use function assert;

/**
 * @package App\Workplace
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
class WorkplaceRepository extends BaseRepository implements WorkplaceRepositoryInterface
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['name', 'slug'];

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @return array<int, Entity>
     *
     * @throws NotSupported
     */
    public function findByMember(User $user): array
    {
        $qb = $this->createQueryBuilder('w')
            ->select('DISTINCT w')
            ->innerJoin('w.members', 'm')
            ->andWhere('m.id = :user')
            ->setParameter('user', $user->getId(), 'uuid_binary_ordered_time');

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NotSupported
     * @throws NonUniqueResultException
     */
    public function findOneBySlugAndMember(User $user, string $slug): ?Entity
    {
        $qb = $this->createQueryBuilder('w')
            ->innerJoin('w.members', 'm')
            ->andWhere('w.slug = :slug')
            ->andWhere('m.id = :user')
            ->setParameter('slug', $slug)
            ->setParameter('user', $user->getId(), 'uuid_binary_ordered_time')
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();
        assert($result === null || $result instanceof Entity);

        return $result;
    }
}
