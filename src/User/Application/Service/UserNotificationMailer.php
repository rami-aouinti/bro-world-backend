<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Service\Interfaces\UserNotificationMailerInterface;
use App\User\Domain\Entity\Event;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\EventReminderWindow;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class UserNotificationMailer implements UserNotificationMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly string $defaultSenderEmail,
    ) {
    }

    public function sendEventReminder(Event $event, EventReminderWindow $window): void
    {
        $user = $event->getUser();
        $locale = $user->getLocale()->value;

        $email = (new Email())
            ->from($this->defaultSenderEmail)
            ->to($user->getEmail())
            ->subject(
                $this->translator->trans(
                    'emails.event_reminder.subject.' . $window->value,
                    ['%event%' => $event->getTitle()],
                    'emails',
                    $locale,
                )
            )
            ->html(
                $this->twig->render('Emails/event_reminder.html.twig', [
                    'user' => $user,
                    'event' => $event,
                    'locale' => $locale,
                    'reminder_window' => $window->value,
                ])
            );

        $this->mailer->send($email);
    }

    public function sendBirthdayGreeting(User $user): void
    {
        $locale = $user->getLocale()->value;

        $email = (new Email())
            ->from($this->defaultSenderEmail)
            ->to($user->getEmail())
            ->subject(
                $this->translator->trans(
                    'emails.birthday.subject',
                    [],
                    'emails',
                    $locale,
                )
            )
            ->html(
                $this->twig->render('Emails/birthday_greeting.html.twig', [
                    'user' => $user,
                    'locale' => $locale,
                ])
            );

        $this->mailer->send($email);
    }
}
