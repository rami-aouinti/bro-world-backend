<?php

declare(strict_types=1);

namespace App\User\Transport\Command\User;

use App\General\Transport\Command\Traits\SymfonyStyleTrait;
use App\User\Application\Service\Interfaces\UserNotificationMailerInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: SendBirthdayGreetingsCommand::NAME,
    description: 'Send happy birthday emails to users celebrating today.',
)]
class SendBirthdayGreetingsCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'user:send-birthday-greetings';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserNotificationMailerInterface $notificationMailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $today = new DateTimeImmutable('today');
        $users = $this->userRepository->findUsersWithBirthdayOnDate($today);

        foreach ($users as $user) {
            $this->notificationMailer->sendBirthdayGreeting($user);
        }

        if ($input->isInteractive()) {
            $io->success(sprintf('Sent %d birthday greeting(s).', count($users)));
        }

        return Command::SUCCESS;
    }
}
