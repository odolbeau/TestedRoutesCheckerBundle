<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle;

/**
 * @internal
 */
final class IgnoredRoutesStorage
{
    public function __construct(
        private readonly string $file,
    ) {
    }

    public function reset(): void
    {
        file_put_contents($this->file, '');
    }

    public function saveRoute(string $route): void
    {
        file_put_contents($this->file, "$route\n", \FILE_APPEND | \LOCK_EX);
    }

    /**
     * @param string[] $routes
     */
    public function saveRoutes(array $routes): void
    {
        if ([] === $routes) {
            return;
        }

        file_put_contents($this->file, implode("\n", $routes)."\n", \FILE_APPEND | \LOCK_EX);
    }

    /**
     * @return string[]
     */
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->getLines() as [, $route]) {
            if (null !== $route) {
                $routes[] = $route;
            }
        }

        return array_values(array_unique($routes));
    }

    /**
     * Returns every line of the file as a [raw line, route] pair.
     * The route is null for empty lines and full line comments.
     *
     * @return list<array{string, string|null}>
     */
    public function getLines(): array
    {
        if (!file_exists($this->file)) {
            throw new \InvalidArgumentException("File \"{$this->file}\"does not exists, unable to load ignored routes!");
        }

        if (false === $lines = @file($this->file, \FILE_IGNORE_NEW_LINES)) {
            throw new \RuntimeException('Unable to load ignored routes from given file.');
        }

        return array_map(static function (string $line): array {
            if ('' === $line || str_starts_with($line, '#')) {
                return [$line, null];
            }

            if (false === $pos = stripos($line, ' #')) {
                return [$line, $line];
            }

            return [$line, mb_substr($line, 0, $pos)];
        }, $lines);
    }

    /**
     * Replaces the whole content of the file.
     *
     * @param string[] $lines
     */
    public function rewrite(array $lines): void
    {
        file_put_contents($this->file, [] === $lines ? '' : implode("\n", $lines)."\n", \LOCK_EX);
    }
}
