<?php

declare(strict_types=1);

namespace App\Tool\Application\DTO\Review;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Application\DTO\RestDto;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\Tool\Domain\Entity\Review as Entity;
use Override;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Review
 *
 * @method self|RestDtoInterface get(string $id)
 * @method self|RestDtoInterface patch(RestDtoInterface $dto)
 * @method Entity|EntityInterface update(EntityInterface $entity)
 */
class Review extends RestDto
{

    protected float $rating = 0;

    protected string $comment = '';


    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->setVisited('rating');
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->setVisited('comment');
        $this->comment = $comment;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param EntityInterface|Entity $entity
     */
    #[Override]
    public function load(EntityInterface $entity): self
    {
        if ($entity instanceof Entity) {
            $this->id = $entity->getId();
            $this->rating = $entity->getRating();
            $this->comment = $entity->getComment();
        }

        return $this;
    }
}
