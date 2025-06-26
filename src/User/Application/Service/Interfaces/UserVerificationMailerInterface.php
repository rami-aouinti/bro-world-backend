<?php

declare(strict_types=1);

namespace App\User\Application\Service\Interfaces;

use App\User\Domain\Entity\User;

/**
 *
 */
interface UserVerificationMailerInterface
{
    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found.
     *
     * Method is override for performance reasons see link below.
     *
     * @see http://symfony2-document.readthedocs.org/en/latest/cookbook/security/entity_provider.html
     *      #managing-roles-in-the-database
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void;

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found.
     *
     * Method is override for performance reasons see link below.
     *
     * @see http://symfony2-document.readthedocs.org/en/latest/cookbook/security/entity_provider.html
     *      #managing-roles-in-the-database
     */
    public function sendVerificationPassword(User $user, string $verificationUrl): void;
}
