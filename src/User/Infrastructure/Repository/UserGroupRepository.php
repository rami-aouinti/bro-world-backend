<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\UserGroup as Entity;
use App\User\Domain\Repository\Interfaces\UserGroupRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

use function sprintf;

/**
 * @package App\User
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
class UserGroupRepository extends BaseRepository implements UserGroupRepositoryInterface
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['role', 'name'];

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @throws Exception
     * @return array
     */
    public function countUserGroupsByMonth(): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('YEAR(b.createdAt) AS year, MONTH(b.createdAt) AS month, COUNT(b.id) AS count')
            ->groupBy('year, month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('month', 'ASC');

        $result = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($result as $row) {
            $key = sprintf('%04d-%02d', $row['year'], $row['month']);
            $counts[$key] = (int) $row['count'];
        }

        $firstKey = array_key_first($counts) ?? (new DateTimeImmutable('now'))->format('Y-m');
        $lastKey = (new DateTimeImmutable('now'))->format('Y-m');

        $fullMonths = $this->generateMonthRange($firstKey, $lastKey);

        $complete = [];
        foreach ($fullMonths as $month) {
            $complete[$month] = $counts[$month] ?? 0;
        }

        return $complete;
    }

    /**
     * @param string $start
     * @param string $end
     *
     * @throws Exception
     * @return array
     */
    private function generateMonthRange(string $start, string $end): array
    {
        $months = [];
        $startDate = new DateTimeImmutable($start . '-01');
        $endDate = new DateTimeImmutable($end . '-01');

        while ($startDate <= $endDate) {
            $months[] = $startDate->format('Y-m');
            $startDate = $startDate->modify('+1 month');
        }

        return $months;
    }
}
