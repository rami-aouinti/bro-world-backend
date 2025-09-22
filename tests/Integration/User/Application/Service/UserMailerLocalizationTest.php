<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\Service;

use App\General\Domain\Enum\Language;
use App\General\Domain\Enum\Locale;
use App\User\Application\Service\UserRegistrationMailer;
use App\User\Application\Service\UserVerificationMailer;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\InMemoryTransport;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function sprintf;

/**
 * @package App\Tests\Integration\User\Application\Service
 */
class UserMailerLocalizationTest extends KernelTestCase
{
    #[DataProvider('provideRegistrationLocales')]
    public function testRegistrationMailerUsesUserLocale(Locale $locale, string $expectedSubject, string $expectedButtonText): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $transport = new InMemoryTransport();
        $mailer = new Mailer($transport);

        $service = new UserRegistrationMailer(
            $mailer,
            $container->get(Environment::class),
            $container->get(TranslatorInterface::class),
        );

        $user = $this->createUserWithLocale($locale);
        $user->setFirstName('John');

        $service->sendVerificationEmail($user, 'https://example.com/verify');

        $messages = $transport->getSent();
        self::assertCount(1, $messages);

        $email = $messages[0]->getOriginalMessage();
        self::assertSame($expectedSubject, $email->getSubject());
        $html = $email->getHtmlBody();
        self::assertStringContainsString(sprintf('lang="%s"', $locale->value), $html);
        self::assertStringContainsString($expectedButtonText, $html);
    }

    #[DataProvider('provideActivationLocales')]
    public function testVerificationMailerUsesUserLocale(Locale $locale, string $expectedSubject, string $expectedInstructions): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $transport = new InMemoryTransport();
        $mailer = new Mailer($transport);

        $service = new UserVerificationMailer(
            $mailer,
            $container->get(Environment::class),
            $container->get(TranslatorInterface::class),
        );

        $user = $this->createUserWithLocale($locale);
        $user->setFirstName('John');

        $service->sendVerificationEmail($user, '123456');

        $messages = $transport->getSent();
        self::assertCount(1, $messages);

        $email = $messages[0]->getOriginalMessage();
        self::assertSame($expectedSubject, $email->getSubject());
        $html = $email->getHtmlBody();
        self::assertStringContainsString(sprintf('lang="%s"', $locale->value), $html);
        self::assertStringContainsString($expectedInstructions, $html);
    }

    /**
     * @return iterable<string, array{Locale, string, string}>
     */
    public static function provideRegistrationLocales(): iterable
    {
        yield 'russian' => [Locale::RU, 'Подтверждение электронной почты', 'Подтвердить адрес электронной почты'];
        yield 'finnish' => [Locale::FI, 'Sähköpostiosoitteen vahvistus', 'Vahvista sähköpostiosoite'];
    }

    /**
     * @return iterable<string, array{Locale, string, string}>
     */
    public static function provideActivationLocales(): iterable
    {
        yield 'ukrainian' => [Locale::UA, 'Код підтвердження електронної пошти', 'Щоб підтвердити електронну адресу'];
        yield 'english' => [Locale::EN, 'Email Verification Code', 'To verify your email address'];
    }

    private function createUserWithLocale(Locale $locale): User
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setLocale($locale);
        $user->setLanguage(Language::from($locale->value));

        return $user;
    }
}
