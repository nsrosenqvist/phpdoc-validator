<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Aggregates validation results across multiple files.
 */
final class Report
{
    /**
     * @var list<FileReport>
     */
    private array $fileReports = [];

    private int $filesScanned = 0;

    public function addFileReport(FileReport $report): void
    {
        $this->filesScanned++;

        if ($report->hasIssues()) {
            $this->fileReports[] = $report;
        }
    }

    /**
     * @return list<FileReport>
     */
    public function getFileReports(): array
    {
        return $this->fileReports;
    }

    public function getFilesScanned(): int
    {
        return $this->filesScanned;
    }

    public function getFilesWithIssues(): int
    {
        return count($this->fileReports);
    }

    public function getTotalIssues(): int
    {
        $count = 0;
        foreach ($this->fileReports as $fileReport) {
            $count += $fileReport->getIssueCount();
        }

        return $count;
    }

    public function getParseErrorCount(): int
    {
        $count = 0;
        foreach ($this->fileReports as $fileReport) {
            if ($fileReport->hasParseError()) {
                $count++;
            }
        }

        return $count;
    }

    public function hasIssues(): bool
    {
        return $this->fileReports !== [];
    }

    public function isClean(): bool
    {
        return !$this->hasIssues();
    }
}
