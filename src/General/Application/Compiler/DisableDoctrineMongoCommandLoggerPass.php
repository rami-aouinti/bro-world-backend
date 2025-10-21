<?php

declare(strict_types=1);

namespace App\General\Application\Compiler;

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

        $classesToRemove = [
            'Doctrine\\Bundle\\MongoDBBundle\\APM\\StopwatchCommandLogger',
            'Doctrine\\Bundle\\MongoDBBundle\\APM\\PSRCommandLogger',
        ];

        $serviceIdsToRemove = [];

        /** @var Definition $definition */
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->getClass() !== null && in_array($definition->getClass(), $classesToRemove, true)) {
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
