<?php

declare(strict_types=1);

namespace App\Workplace\Application\Resource;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\Rest\RestResource;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\User\Domain\Entity\User;
use App\Workplace\Application\DTO\Workplace\Workplace as WorkplaceDto;
use App\Workplace\Domain\Entity\Workplace as Entity;
use App\Workplace\Domain\Repository\Interfaces\WorkplaceRepositoryInterface as Repository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @package App\Workplace
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity getReference(string $id, ?string $entityManagerName = null)
 * @method \App\Workplace\Infrastructure\Repository\WorkplaceRepository getRepository()
 * @method Entity[] find(?array $criteria = null, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null, ?string $entityManagerName = null)
 * @method Entity|null findOne(string $id, ?bool $throwExceptionIfNotFound = null, ?string $entityManagerName = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?bool $throwExceptionIfNotFound = null, ?string $entityManagerName = null)
 * @method Entity create(RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity update(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity patch(string $id, RestDtoInterface $dto, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 * @method Entity delete(string $id, ?bool $flush = null, ?string $entityManagerName = null)
 * @method Entity save(EntityInterface $entity, ?bool $flush = null, ?bool $skipValidation = null, ?string $entityManagerName = null)
 *
 * @codingStandardsIgnoreEnd
 */
class WorkplaceResource extends RestResource
{
    /**
     * @param \App\Workplace\Infrastructure\Repository\WorkplaceRepository $repository
     */
    public function __construct(
        Repository $repository,
        private readonly SluggerInterface $slugger,
    ) {
        parent::__construct($repository);
    }

    public function beforeCreate(RestDtoInterface $restDto, EntityInterface $entity): void
    {
        $this->synchronizeSlug($restDto, $entity);
    }

    public function beforeUpdate(string &$id, RestDtoInterface $restDto, EntityInterface $entity): void
    {
        $this->synchronizeSlug($restDto, $entity);
    }

    public function beforePatch(string &$id, RestDtoInterface $restDto, EntityInterface $entity): void
    {
        $this->synchronizeSlug($restDto, $entity);
    }

    /**
     * @return array<int, Entity>
     *
     * @throws NotSupported
     */
    public function findForMember(User $user): array
    {
        return $this->getRepository()->findByMember($user);
    }

    /**
     * @throws NotSupported
     * @throws NonUniqueResultException
     */
    public function findOneForMemberBySlug(User $user, string $slug): Entity
    {
        $workplace = $this->getRepository()->findOneBySlugAndMember($user, $slug);

        if ($workplace === null) {
            throw new NotFoundHttpException('Workplace not found.');
        }

        return $workplace;
    }

    private function synchronizeSlug(RestDtoInterface $restDto, EntityInterface $entity): void
    {
        if (!$restDto instanceof WorkplaceDto || !$entity instanceof Entity) {
            return;
        }

        if (!in_array('name', $restDto->getVisited(), true)) {
            return;
        }

        $slug = $this->slugger->slug($restDto->getName())->lower()->toString();
        $entity->setSlug($slug);
    }
}
