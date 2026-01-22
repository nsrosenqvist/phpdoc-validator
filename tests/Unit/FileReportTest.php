<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileReportTest extends TestCase
{
    #[Test]
    public function hasIssuesReturnsFalseWhenEmpty(): void
    {
        $report = new FileReport('/path/to/file.php');

        $this->assertFalse($report->hasIssues());
        $this->assertSame(0, $report->getIssueCount());
    }

    #[Test]
    public function hasIssuesReturnsTrueWhenHasMethodIssues(): void
    {
        $report = new FileReport('/path/to/file.php');
        $method = new MethodInfo('testMethod', 10, []);
        $issues = [new Issue(Issue::TYPE_EXTRA_PARAM, 'name', 'Extra param')];

        $report->addMethodIssues($method, $issues);

        $this->assertTrue($report->hasIssues());
        $this->assertSame(1, $report->getIssueCount());
    }

    #[Test]
    public function hasIssuesReturnsTrueWhenHasParseError(): void
    {
        $report = new FileReport('/path/to/file.php', 'Parse error');

        $this->assertTrue($report->hasIssues());
        $this->assertTrue($report->hasParseError());
    }

    #[Test]
    public function addMethodIssuesIgnoresEmptyArray(): void
    {
        $report = new FileReport('/path/to/file.php');
        $method = new MethodInfo('testMethod', 10, []);

        $report->addMethodIssues($method, []);

        $this->assertFalse($report->hasIssues());
        $this->assertSame([], $report->getMethodIssues());
    }

    #[Test]
    public function getMethodIssuesReturnsCorrectStructure(): void
    {
        $report = new FileReport('/path/to/file.php');
        $method = new MethodInfo('testMethod', 10, ['name' => 'string'], null, null, 'MyClass');
        $issues = [
            new Issue(Issue::TYPE_EXTRA_PARAM, 'extra', 'Extra param'),
            new Issue(Issue::TYPE_TYPE_MISMATCH, 'name', 'Type mismatch'),
        ];

        $report->addMethodIssues($method, $issues);

        $methodIssues = $report->getMethodIssues();
        $this->assertCount(1, $methodIssues);

        $key = array_key_first($methodIssues);
        $this->assertSame($method, $methodIssues[$key]['method']);
        $this->assertSame($issues, $methodIssues[$key]['issues']);
    }

    #[Test]
    public function getIssueCountSumsAllIssues(): void
    {
        $report = new FileReport('/path/to/file.php');

        $method1 = new MethodInfo('method1', 10, []);
        $method2 = new MethodInfo('method2', 20, []);

        $report->addMethodIssues($method1, [
            new Issue(Issue::TYPE_EXTRA_PARAM, 'a', 'msg'),
            new Issue(Issue::TYPE_EXTRA_PARAM, 'b', 'msg'),
        ]);

        $report->addMethodIssues($method2, [
            new Issue(Issue::TYPE_TYPE_MISMATCH, 'c', 'msg'),
        ]);

        $this->assertSame(3, $report->getIssueCount());
    }
}
