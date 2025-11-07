<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (!interface_exists('MongoDB\\Driver\\Monitoring\\CommandSubscriber')) {
        $container->services()->remove('doctrine_mongodb.odm.apm.command_logger.stopwatch');
    }
};
