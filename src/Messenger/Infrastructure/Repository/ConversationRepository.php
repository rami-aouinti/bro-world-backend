<?php

declare(strict_types=1);

namespace App\Messenger\Infrastructure\Repository;

use App\General\Infrastructure\Repository\BaseRepository;
use App\Messenger\Domain\Entity\Conversation as Entity;
use App\Messenger\Domain\Repository\Interfaces\ConversationRepositoryInterface;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Exception\NotSupported;
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
class ConversationRepository extends BaseRepository implements ConversationRepositoryInterface
{
    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['title'];

    public function __construct(
        protected ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @throws NotSupported
     */
    #[Override]
    public function findByParticipantId(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('DISTINCT c')
            ->innerJoin('c.participants', 'p')
            ->andWhere('p.id = :user')
            ->setParameter('user', $user->getId(), 'uuid_binary_ordered_time');

        return $qb->getQuery()->getResult();
    }
}
