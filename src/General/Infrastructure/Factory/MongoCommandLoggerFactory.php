<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Factory;

use App\General\Infrastructure\Mongo\NullCommandLogger;
use Doctrine\Bundle\MongoDBBundle\APM\CommandLoggerRegistry;

use function is_object;
use function method_exists;

/**
 * Class MongoCommandLoggerFactory
 *
 */
final readonly class MongoCommandLoggerFactory
{
    public function __construct(private CommandLoggerRegistry $registry)
    {
    }

    public function create(string $managerName): object
    {
        $logger = $this->getLoggerFromRegistry($managerName);

        if ($logger !== null) {
            return $logger;
        }

        return new NullCommandLogger();
    }

    private function getLoggerFromRegistry(string $managerName): ?object
    {
        $registryMethodNames = ['getCommandLogger', 'getCommandLoggerForManager'];

        foreach ($registryMethodNames as $method) {
            if (!method_exists($this->registry, $method)) {
                continue;
            }

            $logger = $this->registry->{$method}($managerName);

            if (is_object($logger) && method_exists($logger, 'getExecutedCommands')) {
                return $logger;
            }
        }

        return null;
    }
}
