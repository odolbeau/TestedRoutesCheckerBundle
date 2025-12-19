<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bab\TestedRoutesCheckerBundle\Analysis\Analyser;
use Bab\TestedRoutesCheckerBundle\Command\CheckCommand;
use Bab\TestedRoutesCheckerBundle\RouteStorage\FileRouteStorage;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('bab_tested_routes_checker_bundle.analysis.analyser', Analyser::class)
            ->args([
                service('router'),
                service('bab_tested_routes_checker_bundle.route_storage.file'),
            ])

        ->set('bab_tested_routes_checker_bundle.route_storage.file', FileRouteStorage::class)
            ->args([
                param('bab_tested_routes_checker_bundle.route_storage_file'),
            ])

        ->set('bab_tested_routes_checker_bundle.command.check', CheckCommand::class)
            ->args([
                service('bab_tested_routes_checker_bundle.analysis.analyser'),
                param('bab_tested_routes_checker_bundle.maximum_number_of_routes_to_display'),
                param('bab_tested_routes_checker_bundle.routes_to_ignore_file'),
            ])
            ->tag('console.command')
    ;
};
