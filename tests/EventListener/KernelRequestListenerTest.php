<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests\EventListener;

use Bab\TestedRoutesCheckerBundle\EventListener\KernelRequestListener;
use Bab\TestedRoutesCheckerBundle\RouteStorage\RouteStorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class KernelRequestListenerTest extends TestCase
{
    public function testMainRequestIsStored(): void
    {
        $storage = $this->createStorage();

        $this->dispatch($storage, HttpKernelInterface::MAIN_REQUEST, 'my_route', 404);

        $this->assertSame([['my_route', 404]], $storage->saved);
    }

    public function testRequestWithoutRouteIsIgnored(): void
    {
        $storage = $this->createStorage();

        $this->dispatch($storage, HttpKernelInterface::MAIN_REQUEST, null, 200);

        $this->assertSame([], $storage->saved);
    }

    private function dispatch(RouteStorageInterface $storage, int $requestType, ?string $route, int $statusCode): void
    {
        $request = new Request();
        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        $kernel = $this->createStub(HttpKernelInterface::class);

        (new KernelRequestListener($storage))(
            new ResponseEvent($kernel, $request, $requestType, new Response('', $statusCode)),
        );
    }

    /**
     * @return RouteStorageInterface&object{saved: list<array{string, int}>}
     */
    private function createStorage(): RouteStorageInterface
    {
        return new class implements RouteStorageInterface {
            /** @var list<array{string, int}> */
            public array $saved = [];

            public function saveRoute(string $route, int $statusCode): void
            {
                $this->saved[] = [$route, $statusCode];
            }

            public function getRoutes(): array
            {
                return [];
            }
        };
    }
}
