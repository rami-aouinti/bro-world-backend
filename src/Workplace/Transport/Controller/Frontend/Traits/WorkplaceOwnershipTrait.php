<?php

declare(strict_types=1);

namespace App\Workplace\Transport\Controller\Frontend\Traits;

use App\User\Domain\Entity\User;
use App\Workplace\Domain\Entity\Workplace;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait WorkplaceOwnershipTrait
{
    private function assertOwnership(Workplace $workplace, User $user): void
    {
        if ($workplace->getOwner()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to manage this workplace.');
        }
    }
}
