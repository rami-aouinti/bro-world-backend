<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Factory;

use Doctrine\Bundle\MongoDBBundle\APM\CommandLoggerRegistry;
use Doctrine\Bundle\MongoDBBundle\Logger\CommandLogger;
use LogicException;

use function method_exists;

final class MongoCommandLoggerFactory
{
    public function __construct(private readonly CommandLoggerRegistry $registry)
    {
    }

    public function create(string $managerName): CommandLogger
    {
        if (method_exists($this->registry, 'getCommandLogger')) {
            $logger = $this->registry->getCommandLogger($managerName);
            if ($logger instanceof CommandLogger) {
                return $logger;
            }
        }

        if (method_exists($this->registry, 'getCommandLoggerForManager')) {
            $logger = $this->registry->getCommandLoggerForManager($managerName);
            if ($logger instanceof CommandLogger) {
                return $logger;
            }
        }

        throw new LogicException('Unable to resolve the Doctrine MongoDB command logger.');
    }
}
