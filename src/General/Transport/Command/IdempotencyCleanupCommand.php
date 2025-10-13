<?php

declare(strict_types=1);

namespace App\General\Transport\Command;

use App\General\Domain\Repository\Interfaces\IdempotencyKeyRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * @package App\General
 */
#[AsCommand(name: 'app:idempotency:purge', description: 'Remove expired idempotency keys from storage')]
class IdempotencyCleanupCommand extends Command
{
    public function __construct(private readonly IdempotencyKeyRepositoryInterface $repository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $removed = $this->repository->purgeExpired(new DateTimeImmutable());

        if ($removed > 0) {
            $io->success(sprintf('Removed %d expired idempotency entries.', $removed));
        } else {
            $io->info('No expired idempotency entries found.');
        }

        return Command::SUCCESS;
    }
}
