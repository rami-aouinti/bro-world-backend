<?php

declare(strict_types=1);

use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    if (!interface_exists('MongoDB\\Driver\\Monitoring\\CommandSubscriber')) {
        $services->remove('doctrine_mongodb.odm.apm.command_logger.stopwatch');

        return;
    }

    $services->alias(DocumentManagerInterface::class, 'doctrine_mongodb.odm.default_document_manager');
};
