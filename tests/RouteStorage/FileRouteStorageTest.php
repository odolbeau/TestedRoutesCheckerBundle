<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests\RouteStorage;

use Bab\TestedRoutesCheckerBundle\RouteStorage\FileRouteStorage;
use PHPUnit\Framework\TestCase;

final class FileRouteStorageTest extends TestCase
{
    public function testStorage(): void
    {
        $storage = new FileRouteStorage(__DIR__.'/../../var/cache/test_cache_file_'.bin2hex(random_bytes(5)));

        $storage->saveRoute('route1', 200);
        $storage->saveRoute('route2', 500);
        $storage->saveRoute('route3', 403);
        $storage->saveRoute('route2', 401);

        $this->assertSame([
            'route1' => [200],
            'route2' => [500, 401],
            'route3' => [403],
        ], $storage->getRoutes());
    }
}
