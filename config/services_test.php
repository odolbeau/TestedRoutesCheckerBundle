<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\HttpKernel\KernelEvents;
use Bab\TestedRoutesCheckerBundle\EventListener\KernelRequestListener;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('bab_tested_routes_checker_bundle.listener.kernel_request_listener', KernelRequestListener::class)
            ->args([
                service('bab_tested_routes_checker_bundle.route_storage.file'),
            ])
            ->tag('kernel.event_listener', ['event' => KernelEvents::RESPONSE])
    ;
};
