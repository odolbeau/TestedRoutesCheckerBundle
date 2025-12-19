<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests;

use Bab\TestedRoutesCheckerBundle\Command\CheckCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

final class FunctionalTest extends TestCase
{
    public function testBundleInit(): void
    {
        $kernel = new TestKernel([
            'framework' => [
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => [
                    'log' => true,
                ],
                'router' => [
                    'resource' => '',
                ],
            ],
        ]);
        $kernel->boot();

        $container = $kernel->getContainer();
        $this->assertInstanceOf(Container::class, $container);

        $removedServices = array_keys($container->getRemovedIds());
        $this->assertTrue(\in_array('bab_tested_routes_checker_bundle.command.check', $removedServices));

        $command = $container->get(CheckCommand::class);
        $this->assertInstanceOf(CheckCommand::class, $command);
    }
}
