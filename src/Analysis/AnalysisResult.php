<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Analysis;

/**
 * @internal
 */
final readonly class AnalysisResult
{
    /**
     * @param string[] $routes
     * @param string[] $testedRoutes
     * @param string[] $successfullyTestedRoutes
     */
    public function __construct(
        private array $routes,
        private array $testedRoutes,
        private array $successfullyTestedRoutes,
    ) {
    }

    /**
     * @return string[]
     */
    public function getTestedRoutes(): array
    {
        return $this->testedRoutes;
    }

    /**
     * @return string[]
     */
    public function getNotTestedRoutes(): array
    {
        return array_values(array_diff($this->routes, $this->testedRoutes));
    }

    /**
     * @return string[]
     */
    public function getSuccessfullyTestedRoutes(): array
    {
        return $this->successfullyTestedRoutes;
    }

    /**
     * @return string[]
     */
    public function getNotSuccessfullyTestedRoutes(): array
    {
        return array_values(array_diff($this->testedRoutes, $this->successfullyTestedRoutes));
    }
}
