<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests\Analysis;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Bab\TestedRoutesCheckerBundle\Analysis\Analyser;
use Bab\TestedRoutesCheckerBundle\Analysis\AnalysisResult;
use Bab\TestedRoutesCheckerBundle\RouteStorage\RouteStorageInterface;

final class AnalyserTest extends TestCase
{
    public function testAnalysis(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('route1', new Route('/route1'));
        $routeCollection->add('route2', new Route('/route2'));
        $routeCollection->add('route3', new Route('/route3'));
        $routeCollection->add('ignored_route1', new Route('/ignored_route1'));
        $routeCollection->add('_wdt', new Route('/_wdt'));
        $routeCollection->add('_wdt_stylesheet', new Route('/_wdt_stylesheet'));

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
                ->method('getRouteCollection')
                ->willReturn($routeCollection);

        /** @®ar RouteStorageInterface&MockObject $routeStorage */
        $routeStorage = $this->createMock(RouteStorageInterface::class);
        $routeStorage->expects($this->exactly(2))
                ->method('getRoutes')
                ->willReturn(['route1' => [200], 'route2' => [404]]);

        $analyser = new Analyser($router, $routeStorage);

        $result = $analyser->run(['ignored_.*']);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertSame(['route1', 'route2'], $result->getTestedRoutes());
        $this->assertSame(['route3'], $result->getNotTestedRoutes());
        $this->assertSame(['route1'], $result->getSuccessfullyTestedRoutes());
        $this->assertSame(['route2'], $result->getNotSuccessfullyTestedRoutes());
    }
}
