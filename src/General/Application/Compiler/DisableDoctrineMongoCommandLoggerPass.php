<?php

declare(strict_types=1);

namespace App\General\Application\Compiler;

use Doctrine\Bundle\MongoDBBundle\APM\StopwatchCommandLogger;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @package App\General
 */
class DisableDoctrineMongoCommandLoggerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (interface_exists(CommandSubscriber::class)) {
            return;
        }

        $serviceIdsToRemove = [];

        /** @var Definition $definition */
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->getClass() === StopwatchCommandLogger::class) {
                $serviceIdsToRemove[] = $id;
            }
        }

        foreach ($serviceIdsToRemove as $id) {
            $container->removeDefinition($id);
        }

        foreach ($container->getAliases() as $id => $alias) {
            if (in_array((string) $alias, $serviceIdsToRemove, true)) {
                $container->removeAlias($id);
            }
        }
    }
}
