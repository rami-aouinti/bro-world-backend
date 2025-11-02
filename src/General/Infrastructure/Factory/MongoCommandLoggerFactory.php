<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Factory;

use Doctrine\Bundle\MongoDBBundle\APM\CommandLoggerRegistry;
use LogicException;

use function is_object;
use function method_exists;

final class MongoCommandLoggerFactory
{
    public function __construct(private readonly CommandLoggerRegistry $registry)
    {
    }

    public function create(string $managerName): object
    {
        $logger = $this->getLoggerFromRegistry($managerName);

        if ($logger !== null) {
            return $logger;
        }

        throw new LogicException('Unable to resolve the Doctrine MongoDB command logger.');
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
