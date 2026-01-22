<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use NsRosenqvist\PhpDocValidator\Report;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReportTest extends TestCase
{
    #[Test]
    public function newReportIsClean(): void
    {
        $report = new Report();

        $this->assertTrue($report->isClean());
        $this->assertFalse($report->hasIssues());
        $this->assertSame(0, $report->getFilesScanned());
        $this->assertSame(0, $report->getFilesWithIssues());
        $this->assertSame(0, $report->getTotalIssues());
    }

    #[Test]
    public function addFileReportIncrementsScannedCount(): void
    {
        $report = new Report();

        $report->addFileReport(new FileReport('/path/file1.php'));
        $report->addFileReport(new FileReport('/path/file2.php'));

        $this->assertSame(2, $report->getFilesScanned());
    }

    #[Test]
    public function cleanFilesAreNotStoredInReports(): void
    {
        $report = new Report();

        $report->addFileReport(new FileReport('/path/clean.php'));

        $this->assertSame(1, $report->getFilesScanned());
        $this->assertSame(0, $report->getFilesWithIssues());
        $this->assertSame([], $report->getFileReports());
    }

    #[Test]
    public function filesWithIssuesAreStored(): void
    {
        $report = new Report();

        $fileReport = new FileReport('/path/issues.php');
        $method = new MethodInfo('test', 10, []);
        $fileReport->addMethodIssues($method, [new Issue(Issue::TYPE_EXTRA_PARAM, 'x', 'msg')]);

        $report->addFileReport($fileReport);

        $this->assertSame(1, $report->getFilesScanned());
        $this->assertSame(1, $report->getFilesWithIssues());
        $this->assertCount(1, $report->getFileReports());
    }

    #[Test]
    public function hasIssuesReturnsTrueWhenFilesHaveIssues(): void
    {
        $report = new Report();

        $fileReport = new FileReport('/path/issues.php');
        $method = new MethodInfo('test', 10, []);
        $fileReport->addMethodIssues($method, [new Issue(Issue::TYPE_EXTRA_PARAM, 'x', 'msg')]);

        $report->addFileReport($fileReport);

        $this->assertTrue($report->hasIssues());
        $this->assertFalse($report->isClean());
    }

    #[Test]
    public function getTotalIssuesSumsAcrossFiles(): void
    {
        $report = new Report();

        $file1 = new FileReport('/path/file1.php');
        $method1 = new MethodInfo('test1', 10, []);
        $file1->addMethodIssues($method1, [
            new Issue(Issue::TYPE_EXTRA_PARAM, 'a', 'msg'),
            new Issue(Issue::TYPE_EXTRA_PARAM, 'b', 'msg'),
        ]);

        $file2 = new FileReport('/path/file2.php');
        $method2 = new MethodInfo('test2', 20, []);
        $file2->addMethodIssues($method2, [
            new Issue(Issue::TYPE_TYPE_MISMATCH, 'c', 'msg'),
        ]);

        $report->addFileReport($file1);
        $report->addFileReport($file2);

        $this->assertSame(3, $report->getTotalIssues());
    }

    #[Test]
    public function getParseErrorCountReturnsCorrectCount(): void
    {
        $report = new Report();

        $report->addFileReport(new FileReport('/path/ok.php'));
        $report->addFileReport(new FileReport('/path/broken1.php', 'Parse error 1'));
        $report->addFileReport(new FileReport('/path/broken2.php', 'Parse error 2'));

        $this->assertSame(2, $report->getParseErrorCount());
    }
}
