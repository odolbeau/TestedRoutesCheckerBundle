<?php

declare(strict_types=1);

namespace Bab\TestedRoutesCheckerBundle\Tests\Command;

use Bab\TestedRoutesCheckerBundle\Analysis\Analyser;
use Bab\TestedRoutesCheckerBundle\Analysis\AnalysisResult;
use Bab\TestedRoutesCheckerBundle\Command\CheckCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{
    public function testWhenEverythingOk(): void
    {
        $analyser = $this->createMock(Analyser::class);
        $analyser->expects($this->once())->method('run')->willReturn(new AnalysisResult(
            routes: ['route1', 'route2', 'route3'],
            testedRoutes: ['route1', 'route2', 'route3'],
            successfullyTestedRoutes: ['route1', 'route2', 'route3'],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, __DIR__.'/ignored_routes'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Congrats, all routes have been tested!', $output);
    }

    public function testWithUntestedRoutes(): void
    {
        $analyser = $this->createMock(Analyser::class);
        $analyser->expects($this->once())->method('run')->willReturn(new AnalysisResult(
            routes: ['route1', 'route2', 'route3'],
            testedRoutes: ['route1'],
            successfullyTestedRoutes: ['route1'],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, __DIR__.'/ignored_routes'));
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Found 2 non tested routes!', $output);
    }

    public function testWithNotSuccessfullyTestedRoutes(): void
    {
        $analyser = $this->createMock(Analyser::class);
        $analyser->expects($this->once())->method('run')->willReturn(new AnalysisResult(
            routes: ['route1', 'route2', 'route3'],
            testedRoutes: ['route1', 'route2', 'route3'],
            successfullyTestedRoutes: ['route1'],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, __DIR__.'/ignored_routes'));
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] Found 2 routes which are not successfully tested', $output);
    }

    public function testWithNotSuccessfullyTestedRoutesAndIgnoreFlag(): void
    {
        $analyser = $this->createMock(Analyser::class);
        $analyser->expects($this->once())->method('run')->willReturn(new AnalysisResult(
            routes: ['route1', 'route2', 'route3'],
            testedRoutes: ['route1', 'route2', 'route3'],
            successfullyTestedRoutes: ['route1'],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, __DIR__.'/ignored_routes'));
        $commandTester->execute(['--ignore-not-successfully-tested-routes' => true]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[WARNING] Found 2 routes which are not successfully tested', $output);
    }

    public function testGenerateBaselineKeepsUsefulEntriesAndRemovesUselessOnes(): void
    {
        $file = sys_get_temp_dir().'/trc_baseline_'.bin2hex(random_bytes(5));
        file_put_contents($file, "# Legacy\na # still untested\ntested\ngone\n^admin_.*\na\n");

        $analyser = $this->createMock(Analyser::class);
        $analyser->method('run')->willReturn(new AnalysisResult(
            routes: ['a', 'b', 'c', 'tested', 'admin_x'],
            testedRoutes: ['tested'],
            successfullyTestedRoutes: ['tested'],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, $file));
        $commandTester->execute(['--generate-baseline' => true]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertSame("# Legacy\na # still untested\n^admin_.*\nb\nc\n", file_get_contents($file));
        $this->assertStringContainsString('2 kept, 2 added, 3 removed', $commandTester->getDisplay());

        unlink($file);
    }

    public function testGenerateBaselineCreatesMissingFile(): void
    {
        $file = sys_get_temp_dir().'/trc_baseline_'.bin2hex(random_bytes(5));

        $analyser = $this->createMock(Analyser::class);
        $analyser->method('run')->willReturn(new AnalysisResult(
            routes: ['a', 'b'],
            testedRoutes: ['b'],
            successfullyTestedRoutes: [],
        ));

        $commandTester = new CommandTester(new CheckCommand($analyser, 10, $file));
        $commandTester->execute(['--generate-baseline' => true, '--ignore-not-successfully-tested-routes' => true]);

        $this->assertSame("a\nb\n", file_get_contents($file));

        unlink($file);
    }
}
