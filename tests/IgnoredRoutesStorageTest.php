<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Bab\TestedRoutesCheckerBundle\IgnoredRoutesStorage;

final class IgnoredRoutesStorageTest extends TestCase
{
    public function testStorage(): void
    {
        $storage = new IgnoredRoutesStorage(__DIR__.'/../var/cache/test_ignored_routes');

        $storage->reset();

        $this->assertSame([], $storage->getRoutes());

        $storage->saveRoutes(['route5', 'route6', 'route7']);

        $this->assertSame(['route5', 'route6', 'route7'], $storage->getRoutes());

        $storage->reset();

        $this->assertSame([], $storage->getRoutes());

        $storage->saveRoute('route1');
        $storage->saveRoute('route2 # This comment will be ignored');
        $storage->saveRoute(''); // Empty line will be ignored
        $storage->saveRoute('route3');
        $storage->saveRoute('# This is a comment which will be ignored');
        $storage->saveRoute('route2');
        $storage->saveRoutes(['route2', 'route4', 'route5']);

        $this->assertSame(['route1', 'route2', 'route3', 'route4', 'route5'], $storage->getRoutes());
    }
}
