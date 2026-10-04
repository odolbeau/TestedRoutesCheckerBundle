<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle;

/**
 * @internal
 */
final class RouteMatcher
{
    /**
     * @param string[] $patterns Route names or regular expressions (without delimiters)
     */
    public static function matchesAny(string $route, array $patterns): bool
    {
        if (\in_array($route, $patterns)) {
            return true;
        }

        foreach ($patterns as $pattern) {
            if (@preg_match("#\b$pattern\b#", $route)) {
                return true;
            }
        }

        return false;
    }
}
