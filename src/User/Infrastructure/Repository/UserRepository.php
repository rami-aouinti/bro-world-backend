<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\General\Domain\Rest\UuidHelper;
use App\General\Infrastructure\Repository\BaseRepository;
use App\User\Domain\Entity\User as Entity;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

use function array_key_exists;
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
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['username', 'firstName', 'lastName', 'email'];

    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        private readonly string $environment,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function isUsernameAvailable(string $username, ?string $id = null): bool
    {
        return $this->isUnique('username', $username, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmailAvailable(string $email, ?string $id = null): bool
    {
        return $this->isUnique('email', $email, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $username, bool $uuid): ?Entity
    {
        /** @var array<string, Entity|null> $cache */
        static $cache = [];

        if (!array_key_exists($username, $cache) || $this->environment === 'test') {
            // Build query
            $queryBuilder = $this
                ->createQueryBuilder('u')
                ->select('u, g, r')
                ->leftJoin('u.userGroups', 'g')
                ->leftJoin('g.role', 'r');

            if ($uuid) {
                $queryBuilder
                    ->where('u.id = :uuid')
                    ->setParameter('uuid', $username, UuidBinaryOrderedTimeType::NAME);
            } else {
                $queryBuilder
                    ->where('u.username = :username OR u.email = :email')
                    ->setParameter('username', $username)
                    ->setParameter('email', $username);
            }

            $query = $queryBuilder->getQuery();

            // phpcs:disable
            /** @var Entity|null $result */
            $result = $query->getOneOrNullResult();

            $cache[$username] = $result;
            // phpcs:enable
        }

        return $cache[$username] instanceof Entity ? $cache[$username] : null;
    }

    public function generateUsername(string $email): string
    {
        $baseUsername = strstr($email, '@', true);
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '_', $baseUsername);

        $username = $baseUsername;
        $counter = 1;
        while (!$this->isUsernameAvailable($username)) {
            $username = $baseUsername . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * @param string $column Column to check
     * @param string $value Value of specified column
     * @param string|null $id User id to ignore
     *
     * @throws NonUniqueResultException
     */
    private function isUnique(string $column, string $value, ?string $id = null): bool
    {
        // Build query
        $query = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.' . $column . ' = :value')
            ->setParameter('value', $value);

        if ($id !== null) {
            $query
                ->andWhere('u.id <> :id')
                ->setParameter('id', $id, UuidHelper::getType($id));
        }

        return $query->getQuery()->getOneOrNullResult() === null;
    }

    /**
     * @throws Exception
     * @return array
     */
    public function countUsersByMonth(): array
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
