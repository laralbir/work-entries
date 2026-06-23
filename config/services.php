<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'), true);

    $container->parameters()
        ->set('app.name', $composer['description'])
        ->set('app.version', $composer['version']);
};
