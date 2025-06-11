<?php

declare(strict_types=1);

namespace App\Tool\Transport\MessageHandler;

use App\Tool\Domain\Message\ContactMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Throwable;
use Twig\Environment;

/**
 * If you need handling multiple - follow https://symfony.com/doc/current/messenger.html#handling-multiple-messages
 *
 * @package App\Tool
 */
#[AsMessageHandler]
class ContactHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(ContactMessage $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * @throws Throwable
     */
    private function handleMessage(ContactMessage $message): void
    {
        $email = (new Email())
            ->from('admin@bro-world.de')
            ->to('rami.aouinti@gmail.com')
            ->subject('Email from Contact')
            ->html(
                $this->twig->render('Emails/contact.html.twig', [
                    'email' => $message->getEmail(),
                    'subject' => $message->getSubject(),
                ])
            );

        $this->mailer->send($email);
    }
}
