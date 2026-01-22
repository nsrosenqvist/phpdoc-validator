<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Feature;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Formatter\GithubActionsFormatter;
use NsRosenqvist\PhpDocValidator\Formatter\JsonFormatter;
use NsRosenqvist\PhpDocValidator\Formatter\PrettyFormatter;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use NsRosenqvist\PhpDocValidator\Report;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    private function createReportWithIssues(): Report
    {
        $report = new Report();

        $file1 = new FileReport('/path/to/File1.php');
        $method1 = new MethodInfo('testMethod', 42, ['name' => 'string'], null, null, 'MyClass');
        $file1->addMethodIssues($method1, [
            new Issue(Issue::TYPE_EXTRA_PARAM, 'extra', 'Extra @param $extra not in method signature'),
            new Issue(Issue::TYPE_TYPE_MISMATCH, 'name', "Type mismatch for \$name: signature has 'string', doc has 'int'", 'string', 'int'),
        ]);
        $report->addFileReport($file1);

        $file2 = new FileReport('/path/to/File2.php');
        $method2 = new MethodInfo('anotherMethod', 10, []);
        $file2->addMethodIssues($method2, [
            new Issue(Issue::TYPE_MISSING_PARAM, 'missing', 'Missing @param documentation for $missing'),
        ]);
        $report->addFileReport($file2);

        return $report;
    }

    private function createCleanReport(): Report
    {
        $report = new Report();
        $report->addFileReport(new FileReport('/path/to/clean.php'));

        return $report;
    }

    #[Test]
    public function prettyFormatterOutputsReadableReport(): void
    {
        $formatter = new PrettyFormatter(false); // No colors for testing
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report, '/path/to');

        $this->assertStringContainsString('PHPDoc Parameter Validation Report', $output);
        $this->assertStringContainsString('File1.php:42', $output);
        $this->assertStringContainsString('MyClass::testMethod()', $output);
        $this->assertStringContainsString('Extra @param $extra', $output);
        $this->assertStringContainsString('Type mismatch', $output);
        $this->assertStringContainsString('Summary:', $output);
        $this->assertStringContainsString('Total issues: 3', $output);
    }

    #[Test]
    public function prettyFormatterShowsSuccessForCleanReport(): void
    {
        $formatter = new PrettyFormatter(false);
        $report = $this->createCleanReport();

        $output = $formatter->format($report);

        $this->assertStringContainsString('No issues found!', $output);
        $this->assertStringContainsString('All files passed validation!', $output);
    }

    #[Test]
    public function prettyFormatterWithColorsIncludesAnsiCodes(): void
    {
        $formatter = new PrettyFormatter(true);
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report);

        // Should contain ANSI escape codes
        $this->assertStringContainsString("\033[", $output);
    }

    #[Test]
    public function prettyFormatterWithoutColorsOmitsAnsiCodes(): void
    {
        $formatter = new PrettyFormatter(false);
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report);

        // Should not contain ANSI escape codes
        $this->assertStringNotContainsString("\033[", $output);
    }

    #[Test]
    public function jsonFormatterOutputsValidJson(): void
    {
        $formatter = new JsonFormatter();
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report, '/path/to');
        /** @var array{summary: array<string, mixed>, files: list<mixed>}|null $data */
        $data = json_decode($output, true);

        $this->assertNotNull($data);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('files', $data);
    }

    #[Test]
    public function jsonFormatterIncludesCorrectSummary(): void
    {
        $formatter = new JsonFormatter();
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report);
        /** @var array{summary: array{filesScanned: int, filesWithIssues: int, totalIssues: int, passed: bool}} $data */
        $data = json_decode($output, true);
        $this->assertIsArray($data);

        $this->assertSame(2, $data['summary']['filesScanned']);
        $this->assertSame(2, $data['summary']['filesWithIssues']);
        $this->assertSame(3, $data['summary']['totalIssues']);
        $this->assertFalse($data['summary']['passed']);
    }

    #[Test]
    public function jsonFormatterIncludesFileDetails(): void
    {
        $formatter = new JsonFormatter();
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report, '/path/to');
        /** @var array{files: list<array{path: string, methods: list<array{name: string, line: int, issues: list<mixed>}>}>} $data */
        $data = json_decode($output, true);
        $this->assertIsArray($data);

        $this->assertCount(2, $data['files']);

        $file1 = $data['files'][0];
        $this->assertSame('File1.php', $file1['path']);
        $this->assertCount(1, $file1['methods']);
        $this->assertSame('MyClass::testMethod', $file1['methods'][0]['name']);
        $this->assertSame(42, $file1['methods'][0]['line']);
        $this->assertCount(2, $file1['methods'][0]['issues']);
    }

    #[Test]
    public function jsonFormatterShowsPassedForCleanReport(): void
    {
        $formatter = new JsonFormatter();
        $report = $this->createCleanReport();

        $output = $formatter->format($report);
        /** @var array{summary: array{passed: bool, totalIssues: int}} $data */
        $data = json_decode($output, true);
        $this->assertIsArray($data);

        $this->assertTrue($data['summary']['passed']);
        $this->assertSame(0, $data['summary']['totalIssues']);
    }

    #[Test]
    public function githubActionsFormatterOutputsAnnotations(): void
    {
        $formatter = new GithubActionsFormatter();
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report, '/path/to');

        // Should contain GitHub Actions workflow commands
        $this->assertStringContainsString('::error file=File1.php,line=42', $output);
        $this->assertStringContainsString('title=Extra @param', $output);
        $this->assertStringContainsString('title=Type mismatch', $output);
        $this->assertStringContainsString('::warning file=File2.php,line=10', $output);
        $this->assertStringContainsString('title=Missing @param', $output);
    }

    #[Test]
    public function githubActionsFormatterIncludesSummaryError(): void
    {
        $formatter = new GithubActionsFormatter();
        $report = $this->createReportWithIssues();

        $output = $formatter->format($report);

        $this->assertStringContainsString('::error::PHPDoc validation failed: 3 issue(s) in 2 file(s)', $output);
    }

    #[Test]
    public function githubActionsFormatterOutputsNothingForCleanReport(): void
    {
        $formatter = new GithubActionsFormatter();
        $report = $this->createCleanReport();

        $output = $formatter->format($report);

        // Should be empty for clean reports
        $this->assertSame('', $output);
    }

    #[Test]
    public function githubActionsFormatterHandlesParseErrors(): void
    {
        $formatter = new GithubActionsFormatter();
        $report = new Report();
        $report->addFileReport(new FileReport('/path/broken.php', 'Syntax error on line 5'));

        $output = $formatter->format($report, '/path');

        $this->assertStringContainsString('::warning file=broken.php,line=1', $output);
        $this->assertStringContainsString('title=Parse error', $output);
    }

    #[Test]
    public function prettyFormatterHandlesParseErrors(): void
    {
        $formatter = new PrettyFormatter(false);
        $report = new Report();
        $report->addFileReport(new FileReport('/path/broken.php', 'Syntax error on line 5'));

        $output = $formatter->format($report, '/path');

        $this->assertStringContainsString('broken.php', $output);
        $this->assertStringContainsString('Parse error', $output);
        $this->assertStringContainsString('Parse errors: 1', $output);
    }

    #[Test]
    public function jsonFormatterIncludesParseErrors(): void
    {
        $formatter = new JsonFormatter();
        $report = new Report();
        $report->addFileReport(new FileReport('/path/broken.php', 'Syntax error'));

        $output = $formatter->format($report);
        /** @var array{summary: array{parseErrors: int}, files: list<array{parseError: string}>} $data */
        $data = json_decode($output, true);
        $this->assertIsArray($data);

        $this->assertSame(1, $data['summary']['parseErrors']);
        $this->assertSame('Syntax error', $data['files'][0]['parseError']);
    }
}
