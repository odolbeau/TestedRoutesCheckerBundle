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

    public function testConcurrentWritesDoNotLoseOrCorruptRoutes(): void
    {
        $file = __DIR__.'/../../var/cache/test_cache_file_'.bin2hex(random_bytes(5));
        $script = \sprintf(
            '$s = new %s(%s); for ($i = 0; $i < 200; ++$i) { $s->saveRoute("route", 200); }',
            '\\'.FileRouteStorage::class,
            var_export($file, true),
        );

        $processes = [];
        for ($i = 0; $i < 4; ++$i) {
            $processes[] = proc_open(
                [\PHP_BINARY, '-r', 'require '.var_export(__DIR__.'/../../vendor/autoload.php', true).';'.$script],
                [],
                $pipes,
            );
        }
        foreach ($processes as $process) {
            $this->assertIsResource($process);
            proc_close($process);
        }

        $this->assertCount(800, (new FileRouteStorage($file))->getRoutes()['route']);
    }
}
