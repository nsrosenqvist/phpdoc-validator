<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Formatter;

use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\Report;

/**
 * Formats validation reports as GitHub Actions workflow commands.
 *
 * @see https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions
 */
final class GithubActionsFormatter implements FormatterInterface
{
    public function format(Report $report, ?string $basePath = null): string
    {
        $lines = [];

        foreach ($report->getFileReports() as $fileReport) {
            $filePath = $basePath !== null
                ? $this->makeRelative($fileReport->filePath, $basePath)
                : $fileReport->filePath;

            if ($fileReport->hasParseError()) {
                $lines[] = $this->formatAnnotation(
                    'warning',
                    $filePath,
                    1,
                    'Parse error',
                    $fileReport->parseError ?? 'Unknown parse error'
                );

                continue;
            }

            foreach ($fileReport->getMethodIssues() as $data) {
                $method = $data['method'];
                $issues = $data['issues'];

                foreach ($issues as $issue) {
                    $level = $this->getAnnotationLevel($issue);
                    $title = $this->getAnnotationTitle($issue);

                    $lines[] = $this->formatAnnotation(
                        $level,
                        $filePath,
                        $method->line,
                        $title,
                        $issue->message
                    );
                }
            }
        }

        // Add summary
        if ($report->hasIssues()) {
            $lines[] = sprintf(
                '::error::PHPDoc validation failed: %d issue(s) in %d file(s)',
                $report->getTotalIssues(),
                $report->getFilesWithIssues()
            );
        }

        return implode("\n", $lines);
    }

    private function formatAnnotation(
        string $level,
        string $file,
        int $line,
        string $title,
        string $message
    ): string {
        // Escape special characters in message
        $message = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $message);
        $title = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $title);

        return sprintf('::%s file=%s,line=%d,title=%s::%s', $level, $file, $line, $title, $message);
    }

    private function getAnnotationLevel(Issue $issue): string
    {
        return match ($issue->type) {
            Issue::TYPE_EXTRA_PARAM => 'error',
            Issue::TYPE_TYPE_MISMATCH => 'error',
            Issue::TYPE_RETURN_MISMATCH => 'error',
            Issue::TYPE_MISSING_PARAM => 'warning',
            Issue::TYPE_MISSING_RETURN => 'warning',
            default => 'notice',
        };
    }

    private function getAnnotationTitle(Issue $issue): string
    {
        return match ($issue->type) {
            Issue::TYPE_EXTRA_PARAM => 'Extra @param',
            Issue::TYPE_TYPE_MISMATCH => 'Type mismatch',
            Issue::TYPE_RETURN_MISMATCH => 'Return type mismatch',
            Issue::TYPE_MISSING_PARAM => 'Missing @param',
            Issue::TYPE_MISSING_RETURN => 'Missing @return',
            default => 'PHPDoc issue',
        };
    }

    private function makeRelative(string $path, string $basePath): string
    {
        $basePath = rtrim($basePath, '/') . '/';

        if (str_starts_with($path, $basePath)) {
            return substr($path, strlen($basePath));
        }

        return $path;
    }
}
