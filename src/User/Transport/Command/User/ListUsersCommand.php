<?php

declare(strict_types=1);

namespace App\User\Transport\Command\User;

use App\General\Transport\Command\Traits\SymfonyStyleTrait;
use App\User\Application\Service\Interfaces\UserElasticsearchServiceInterface;
use App\User\Domain\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\Exception\NotSupported;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\User
 */
#[AsCommand(
    name: self::NAME,
    description: 'Console command to index all users',
)]
class ListUsersCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'user:index';

    /**
     * @throws LogicException
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserElasticsearchServiceInterface $userElasticsearchService,
    ) {
        parent::__construct();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $io->title('Start User Indexation');
        $this->getRows();
        $io->title('Success User Indexation');
        return 0;
    }

    /**
     * Getter method for formatted user rows for console table.
     *
     * @throws NotSupported
     * @return void
     */
    private function getRows(): void
    {
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->userElasticsearchService->indexUserInElasticsearch($user);
        }
    }
}
