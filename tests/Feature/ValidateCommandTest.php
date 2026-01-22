<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Feature;

use NsRosenqvist\PhpDocValidator\Command\ValidateCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ValidateCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private string $fixturesPath;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new ValidateCommand());
        $command = $application->find('validate');
        $this->commandTester = new CommandTester($command);
        $this->fixturesPath = dirname(__DIR__) . '/fixtures';
    }

    #[Test]
    public function returnsZeroForCleanFiles(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/ValidClass.php'],
        ]);

        $this->assertSame(ValidateCommand::EXIT_SUCCESS, $exitCode);
        $this->assertStringContainsString('No issues found', $this->commandTester->getDisplay());
    }

    #[Test]
    public function returnsOneForFilesWithIssues(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/ExtraParamsClass.php'],
        ]);

        $this->assertSame(ValidateCommand::EXIT_ISSUES_FOUND, $exitCode);
        $this->assertStringContainsString('Extra @param', $this->commandTester->getDisplay());
    }

    #[Test]
    public function returnsTwoForNonExistentPath(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => ['/nonexistent/path'],
        ]);

        $this->assertSame(ValidateCommand::EXIT_ERROR, $exitCode);
        $this->assertStringContainsString('does not exist', $this->commandTester->getDisplay());
    }

    #[Test]
    public function jsonFormatOutputsValidJson(): void
    {
        $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/ValidClass.php'],
            '--format' => 'json',
        ]);

        $output = $this->commandTester->getDisplay();
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('summary', $data);
    }

    #[Test]
    public function githubFormatOutputsAnnotations(): void
    {
        $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/ExtraParamsClass.php'],
            '--format' => 'github',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('::error file=', $output);
    }

    #[Test]
    public function excludeOptionFiltersFiles(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [$this->fixturesPath],
            '--exclude' => ['*ExtraParams*', '*TypeMismatch*', '*BrokenSyntax*', '*Missing*', '*Edge*', '*Sample*', '*functions*', '*ReturnTypes*', '*ParamOrder*'],
        ]);

        // With all problematic files excluded, should pass
        $this->assertSame(ValidateCommand::EXIT_SUCCESS, $exitCode);
    }

    #[Test]
    public function missingOptionReportsUndocumentedParams(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/MissingParamsClass.php'],
            '--missing' => true,
        ]);

        $this->assertSame(ValidateCommand::EXIT_ISSUES_FOUND, $exitCode);
        $this->assertStringContainsString('Missing @param', $this->commandTester->getDisplay());
    }

    #[Test]
    public function noColorOptionDisablesColors(): void
    {
        $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/ExtraParamsClass.php'],
            '--no-color' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        // Should not contain ANSI escape codes
        $this->assertStringNotContainsString("\033[", $output);
    }

    #[Test]
    public function defaultPathIsCurrentDirectory(): void
    {
        // Change to fixtures directory and run without path argument
        $originalDir = getcwd();
        chdir($this->fixturesPath);

        try {
            $exitCode = $this->commandTester->execute([
                '--exclude' => ['*BrokenSyntax*'],
            ]);

            // Should find issues in the fixtures directory
            $this->assertSame(ValidateCommand::EXIT_ISSUES_FOUND, $exitCode);
        } finally {
            if ($originalDir !== false) {
                chdir($originalDir);
            }
        }
    }

    #[Test]
    public function handlesMultiplePaths(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [
                $this->fixturesPath . '/ValidClass.php',
                $this->fixturesPath . '/ComplexTypesClass.php',
            ],
        ]);

        $this->assertSame(ValidateCommand::EXIT_SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Files scanned: 2', $output);
    }

    #[Test]
    public function handlesSyntaxErrorsGracefully(): void
    {
        $exitCode = $this->commandTester->execute([
            'paths' => [$this->fixturesPath . '/BrokenSyntax.php'],
        ]);

        // Should still exit with issues found (parse error counts as issue)
        $this->assertSame(ValidateCommand::EXIT_ISSUES_FOUND, $exitCode);
        $this->assertStringContainsString('Parse error', $this->commandTester->getDisplay());
    }

    #[Test]
    public function displaysHelpInformation(): void
    {
        // Test that the command has all expected options defined
        $application = new Application();
        $application->add(new ValidateCommand());
        $command = $application->find('validate');

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('format'));
        $this->assertTrue($definition->hasOption('exclude'));
        $this->assertTrue($definition->hasOption('missing'));
        $this->assertTrue($definition->hasOption('no-color'));
        $this->assertTrue($definition->hasArgument('paths'));

        // Verify command description
        $this->assertStringContainsString('Validate', $command->getDescription());
    }
}
