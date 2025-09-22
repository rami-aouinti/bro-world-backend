<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\User\Application\Service\UserRegistrationMailer;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class UserRegistrationMailerTest extends TestCase
{
    public function testSendVerificationPasswordUsesResetPasswordUrl(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $twig = $this->createMock(Environment::class);

        $resetUrl = 'https://example.com/reset-password?token=token';

        $user = new User();
        $user->setEmail('user@example.com');

        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($resetUrl, $user): bool {
                self::assertSame('admin@bro-world.de', $email->getFrom()[0]->getAddress());
                self::assertSame($user->getEmail(), $email->getTo()[0]->getAddress());
                self::assertStringContainsString($resetUrl, $email->getHtmlBody());

                return true;
            }));

        $twig->expects(self::once())
            ->method('render')
            ->with(
                'Emails/password_verification.html.twig',
                self::callback(static function (array $context) use ($resetUrl, $user): bool {
                    self::assertArrayHasKey('user', $context);
                    self::assertSame($user, $context['user']);
                    self::assertArrayHasKey('reset_password_url', $context);
                    self::assertSame($resetUrl, $context['reset_password_url']);

                    return true;
                })
            )
            ->willReturn(sprintf('<a href="%s">Reset</a>', $resetUrl));

        $mailerService = new UserRegistrationMailer($mailer, $twig);

        $mailerService->sendVerificationPassword($user, $resetUrl);
    }
}
