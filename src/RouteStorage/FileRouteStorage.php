<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\RouteStorage;

/**
 * @internal
 */
final class FileRouteStorage implements RouteStorageInterface
{
    public function __construct(
        private readonly string $file,
    ) {
    }

    #[\Override]
    public function saveRoute(string $route, int $statusCode): void
    {
        if (!file_exists($this->file)) {
            touch($this->file);
        }

        file_put_contents($this->file, "$route|$statusCode\n", \FILE_APPEND);
    }

    #[\Override]
    public function getRoutes(): array
    {
        if (!file_exists($this->file)) {
            throw new \InvalidArgumentException("File \"{$this->file}\" does not exists, did you correclty run tests?");
        }

        if (false === $routes = @file($this->file, \FILE_IGNORE_NEW_LINES)) {
            throw new \RuntimeException('Unable to load routes from given file.');
        }

        $filteredRoutes = [];

        foreach ($routes as $route) {
            $parts = explode('|', $route);

            if (2 !== \count($parts)) {
                throw new \RuntimeException("It looks like the given file ({$this->file}) does not contains valid data. Consider removing it.");
            }

            $name = $parts[0];
            $statusCode = (int) $parts[1];

            if (!\array_key_exists($name, $filteredRoutes)) {
                $filteredRoutes[$name] = [$statusCode];
            } else {
                $filteredRoutes[$name][] = $statusCode;
            }
        }

        return $filteredRoutes;
    }
}
