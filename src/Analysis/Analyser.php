<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Analysis;

use Bab\TestedRoutesCheckerBundle\RouteStorage\RouteStorageInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
class Analyser
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RouteStorageInterface $routeStorage,
    ) {
    }

    /**
     * @param string[] $routesToIgnore
     */
    public function run(array $routesToIgnore): AnalysisResult
    {
        $routesToIgnore = array_merge($this->getDefaultRoutesToIgnore(), $routesToIgnore);

        $routes = $this->filterRoutes(array_keys($this->router->getRouteCollection()->all()), $routesToIgnore);
        $testedRoutes = $this->filterRoutes(array_unique(array_keys($this->routeStorage->getRoutes())), $routesToIgnore);

        $successfullyTestedRoutes = array_keys(array_filter($this->routeStorage->getRoutes(), static function (array $responseCodes): bool {
            foreach ($responseCodes as $responseCode) {
                if ($responseCode < 400) {
                    return true;
                }
            }

            return false;
        }));

        return new AnalysisResult(
            routes: $routes,
            testedRoutes: $testedRoutes,
            successfullyTestedRoutes: $successfullyTestedRoutes,
        );
    }

    /**
     * Return only routes which should not be ignored according to $routesToIgnore.
     *
     * @param string[] $routes
     * @param string[] $routesToIgnore
     *
     * @return string[]
     */
    private function filterRoutes(array $routes, array $routesToIgnore): array
    {
        $filteredRoutes = [];
        foreach ($routes as $route) {
            if (\in_array($route, $routesToIgnore)) {
                continue;
            }
            foreach ($routesToIgnore as $routeToIgnore) {
                if (@preg_match("#\b$routeToIgnore\b#", $route)) {
                    continue 2;
                }
            }

            $filteredRoutes[] = $route;
        }

        return $filteredRoutes;
    }

    /**
     * @return string[]
     */
    private function getDefaultRoutesToIgnore(): array
    {
        return [
            '^_profiler.*$',
            '_wdt.*',
            '_webhook_controller',
            '_preview_error',
            'app.swagger',
        ];
    }
}
