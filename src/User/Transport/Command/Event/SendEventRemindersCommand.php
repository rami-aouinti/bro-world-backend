<?php

declare(strict_types=1);

namespace App\User\Transport\Command\Event;

use App\General\Transport\Command\Traits\SymfonyStyleTrait;
use App\User\Application\Service\Interfaces\UserNotificationMailerInterface;
use App\User\Domain\Enum\EventReminderWindow;
use App\User\Domain\Repository\Interfaces\EventRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: SendEventRemindersCommand::NAME,
    description: 'Send reminder emails for today\'s events.',
)]
class SendEventRemindersCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'event:send-reminders';
    private const int FOUR_HOURS_IN_SECONDS = 14400;
    private const int FIFTEEN_MINUTES_IN_SECONDS = 900;

    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly UserNotificationMailerInterface $notificationMailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $now = new DateTimeImmutable('now');
        $startOfDay = $now->setTime(0, 0);
        $endOfDay = $startOfDay->modify('+1 day');

        $events = $this->eventRepository->findEventsWithinPeriod($startOfDay, $endOfDay);
        $remindersSent = 0;

        foreach ($events as $event) {
            $start = DateTimeImmutable::createFromInterface($event->getStart());
            $secondsUntilStart = $start->getTimestamp() - $now->getTimestamp();

            if ($secondsUntilStart <= 0) {
                continue;
            }

            if (
                $secondsUntilStart <= self::FOUR_HOURS_IN_SECONDS
                && $secondsUntilStart > self::FIFTEEN_MINUTES_IN_SECONDS
                && !$event->isFourHourReminderSent()
            ) {
                $this->notificationMailer->sendEventReminder($event, EventReminderWindow::FOUR_HOURS);
                $event->markFourHourReminderSent();
                $this->eventRepository->save($event);
                ++$remindersSent;
            }

            if ($secondsUntilStart <= self::FIFTEEN_MINUTES_IN_SECONDS && !$event->isFifteenMinuteReminderSent()) {
                $this->notificationMailer->sendEventReminder($event, EventReminderWindow::FIFTEEN_MINUTES);
                $event->markFifteenMinuteReminderSent();
                $this->eventRepository->save($event);
                ++$remindersSent;
            }
        }

        if ($input->isInteractive()) {
            $io->success(sprintf('Sent %d reminder(s) for %d event(s).', $remindersSent, count($events)));
        }

        return Command::SUCCESS;
    }
}
