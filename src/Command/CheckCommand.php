<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Command;

use Bab\TestedRoutesCheckerBundle\Analysis\Analyser;
use Bab\TestedRoutesCheckerBundle\IgnoredRoutesStorage;
use Bab\TestedRoutesCheckerBundle\RouteMatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'bab:tested-routes-checker:check',
    description: 'Ensure all routes have been tested during previous PHPUnit run(s).',
)]
class CheckCommand extends Command
{
    public function __construct(
        private readonly Analyser $analyser,
        private readonly int $maximumNumberOfRoutesToDisplay,
        private readonly string $routesToIgnoreFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('maximum-routes-to-display', 'm', InputOption::VALUE_REQUIRED, 'Maximum number of routes to display per section', $this->maximumNumberOfRoutesToDisplay)
            ->addOption('routes-to-ignore', 'i', InputOption::VALUE_REQUIRED, 'A file containing routes to ignore', $this->routesToIgnoreFile)
            ->addOption('generate-baseline', 'g', InputOption::VALUE_NONE, 'Generate the file containing the routes to be ignored')
            ->addOption('ignore-not-successfully-tested-routes', 'S', InputOption::VALUE_NONE, 'Does not fail if a route have not been successfully tested (never returned a 1xx, 2xx or 3xx code).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /* @phpstan-ignore-next-line */
        $maximumRoutesToDisplay = (int) $input->getOption('maximum-routes-to-display');

        $ignoreNotSuccessfullyTestedRoutes = (bool) $input->getOption('ignore-not-successfully-tested-routes');

        $routesToIgnore = [];

        /** @var string $routesToIgnoreFile */
        $routesToIgnoreFile = $input->getOption('routes-to-ignore');
        $ignoredRoutesStorage = new IgnoredRoutesStorage($routesToIgnoreFile);

        try {
            $routesToIgnore = $ignoredRoutesStorage->getRoutes();
        } catch (\InvalidArgumentException $e) {
            $io->warning('Unable to load the given file containing routes to ignore.');
        }

        $result = $this->analyser->run($routesToIgnore);

        $this->showNotSuccessfullyTestedRoutesSection($io, $result->getNotSuccessfullyTestedRoutes(), $ignoreNotSuccessfullyTestedRoutes, $maximumRoutesToDisplay);
        $this->showNotTestedRoutesSection($io, $result->getNotTestedRoutes(), $maximumRoutesToDisplay);

        // If the goal is to generate the baseline, we do it and go out!
        if ($input->getOption('generate-baseline')) {
            $this->generateBaseline($io, $ignoredRoutesStorage, $routesToIgnoreFile, $ignoreNotSuccessfullyTestedRoutes);

            return Command::SUCCESS;
        }

        if (0 < \count($result->getNotTestedRoutes())) {
            return Command::FAILURE;
        }

        if (0 < \count($result->getNotSuccessfullyTestedRoutes())) {
            return $ignoreNotSuccessfullyTestedRoutes ? Command::SUCCESS : Command::FAILURE;
        }

        $io->success('Congrats, all routes have been tested!');

        return Command::SUCCESS;
    }

    /**
     * Keeps the existing entries (and comments) which are still useful, removes the others
     * and appends the routes which are not covered yet.
     */
    private function generateBaseline(SymfonyStyle $io, IgnoredRoutesStorage $storage, string $file, bool $ignoreNotSuccessfullyTestedRoutes): void
    {
        // Only the default ignored routes apply here: we want every route which would be reported without the baseline.
        $result = $this->analyser->run([]);

        $candidates = $result->getNotTestedRoutes();
        if ($ignoreNotSuccessfullyTestedRoutes) {
            $candidates = array_merge($candidates, $result->getNotSuccessfullyTestedRoutes());
        }

        try {
            $existingLines = $storage->getLines();
        } catch (\InvalidArgumentException) {
            $existingLines = [];
        }

        $lines = [];
        $kept = [];
        $removed = 0;
        foreach ($existingLines as [$line, $route]) {
            if (null === $route) {
                $lines[] = $line;

                continue;
            }

            $isUseful = [] !== array_filter($candidates, static fn (string $candidate): bool => RouteMatcher::matchesAny($candidate, [$route]));
            if (!$isUseful || \in_array($route, $kept, true)) {
                ++$removed;

                continue;
            }

            $kept[] = $route;
            $lines[] = $line;
        }

        $added = 0;
        foreach (array_unique($candidates) as $candidate) {
            if (!RouteMatcher::matchesAny($candidate, $kept)) {
                $lines[] = $candidate;
                ++$added;
            }
        }

        $storage->rewrite($lines);

        $io->writeln(\sprintf('%s updated: %d kept, %d added, %d removed', $file, \count($kept), $added, $removed));
    }

    /**
     * @param string[] $routes
     */
    private function showNotSuccessfullyTestedRoutesSection(SymfonyStyle $io, array $routes, bool $ignoreNotSuccessfullyTestedRoutes, int $maximumRoutesToDisplay): void
    {
        if (0 === $count = \count($routes)) {
            return;
        }

        $io->writeln('Some routes have not been successfully tested (they always returned a 4xx or 5xx code) :');
        $io->writeln('');

        $io->listing(\array_slice($routes, 0, $maximumRoutesToDisplay));

        if ($count > $maximumRoutesToDisplay) {
            $io->writeln(\sprintf('... and %d more', $count - $maximumRoutesToDisplay));
        }

        $message = \sprintf('Found %d routes which are not successfully tested', \count($routes));

        if ($ignoreNotSuccessfullyTestedRoutes) {
            $io->warning($message);
        } else {
            $io->error($message);
        }
    }

    /**
     * @param string[] $routes
     */
    private function showNotTestedRoutesSection(SymfonyStyle $io, array $routes, int $maximumRoutesToDisplay): void
    {
        if (0 === $count = \count($routes)) {
            return;
        }

        $io->writeln('Some routes have not been tested :');
        $io->writeln('');

        $io->listing(\array_slice($routes, 0, $maximumRoutesToDisplay));

        if ($count > $maximumRoutesToDisplay) {
            $io->writeln(\sprintf('... and %d more', $count - $maximumRoutesToDisplay));
        }

        $io->error(\sprintf('Found %d non tested route%s!', $count, 1 === $count ? '' : 's'));
    }
}
