<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Service\Interfaces\UserVerificationMailerInterface;
use App\User\Domain\Entity\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @package App\User\User\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */

class UserVerificationMailer implements UserVerificationMailerInterface
{
    private MailerInterface $mailer;
    private Environment $twig;
    private TranslatorInterface $translator;

    public function __construct(MailerInterface $mailer, Environment $twig, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $locale = $user->getLocale()->value;

        $email = (new Email())
            ->from('admin@bro-world.de')
            ->to($user->getEmail())
            ->subject(
                $this->translator->trans('emails.activation.subject', [], 'emails', $locale)
            )
            ->html(
                $this->twig->render('Emails/email_activation_verification.html.twig', [
                    'user' => $user,
                    'verification_code' => $verificationUrl,
                    'locale' => $locale,
                ])
            );

        $this->mailer->send($email);
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendVerificationPassword(User $user, string $verificationUrl): void
    {
        $locale = $user->getLocale()->value;

        $email = (new Email())
            ->from('admin@bro-world.de')
            ->to($user->getEmail())
            ->subject(
                $this->translator->trans('emails.password_reset.subject', [], 'emails', $locale)
            )
            ->html(
                $this->twig->render('Emails/password_verification.html.twig', [
                    'user' => $user,
                    'reset_password_url' => $verificationUrl,
                    'locale' => $locale,
                ])
            );

        $this->mailer->send($email);
    }
}
