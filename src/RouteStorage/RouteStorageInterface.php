<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\RouteStorage;

/**
 * @internal
 */
interface RouteStorageInterface
{
    public function saveRoute(string $route, int $statusCode): void;

    /**
     * Return an array with the route name as key and all known return codes
     * (including duplicates).
     *
     * @return array<string, int[]>
     */
    public function getRoutes(): array;
}
