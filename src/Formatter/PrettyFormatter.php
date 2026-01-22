<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Formatter;

use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\Report;

/**
 * Formats validation reports with nice CLI styling.
 */
final class PrettyFormatter implements FormatterInterface
{
    private bool $useColors;

    public function __construct(bool $useColors = true)
    {
        $this->useColors = $useColors;
    }

    public function format(Report $report, ?string $basePath = null): string
    {
        $output = [];

        $output[] = $this->formatHeader();
        $output[] = '';

        if ($report->isClean()) {
            $output[] = $this->color('✓ No issues found!', 'green');
            $output[] = '';
            $output[] = $this->formatSummary($report);

            return implode("\n", $output);
        }

        foreach ($report->getFileReports() as $fileReport) {
            $filePath = $basePath !== null
                ? $this->makeRelative($fileReport->filePath, $basePath)
                : $fileReport->filePath;

            if ($fileReport->hasParseError()) {
                $output[] = $this->color("{$filePath}", 'yellow');
                $output[] = $this->color("   [!] Parse error: {$fileReport->parseError}", 'yellow');
                $output[] = '';

                continue;
            }

            foreach ($fileReport->getMethodIssues() as $data) {
                $method = $data['method'];
                $issues = $data['issues'];

                $output[] = $this->color("{$filePath}:{$method->line}", 'cyan');
                $output[] = $this->color("   Method: {$method->getFullName()}()", 'white');

                foreach ($issues as $issue) {
                    $icon = $this->getIssueIcon($issue);
                    $color = $this->getIssueColor($issue);
                    $output[] = $this->color("   {$icon}  {$issue->message}", $color);
                }

                $output[] = '';
            }
        }

        $output[] = $this->formatSummary($report);

        return implode("\n", $output);
    }

    private function formatHeader(): string
    {
        $title = 'PHPDoc Parameter Validation Report';
        $separator = str_repeat('=', strlen($title));

        return $this->color($title, 'white', true) . "\n" . $this->color($separator, 'white');
    }

    private function formatSummary(Report $report): string
    {
        $lines = [];

        $lines[] = $this->color('Summary:', 'white', true);
        $lines[] = sprintf('  Files scanned: %d', $report->getFilesScanned());

        if ($report->hasIssues()) {
            $lines[] = $this->color(
                sprintf('  Files with issues: %d', $report->getFilesWithIssues()),
                'yellow'
            );
            $lines[] = $this->color(
                sprintf('  Total issues: %d', $report->getTotalIssues()),
                'red'
            );

            if ($report->getParseErrorCount() > 0) {
                $lines[] = $this->color(
                    sprintf('  Parse errors: %d', $report->getParseErrorCount()),
                    'yellow'
                );
            }
        } else {
            $lines[] = $this->color('  All files passed validation!', 'green');
        }

        return implode("\n", $lines);
    }

    private function getIssueIcon(Issue $issue): string
    {
        return match ($issue->type) {
            Issue::TYPE_EXTRA_PARAM => '[X]',
            Issue::TYPE_TYPE_MISMATCH, Issue::TYPE_RETURN_MISMATCH => '[!]',
            Issue::TYPE_MISSING_PARAM, Issue::TYPE_MISSING_RETURN => '[?]',
            default => '[-]',
        };
    }

    private function getIssueColor(Issue $issue): string
    {
        return match ($issue->type) {
            Issue::TYPE_EXTRA_PARAM => 'red',
            Issue::TYPE_TYPE_MISMATCH, Issue::TYPE_RETURN_MISMATCH => 'yellow',
            Issue::TYPE_MISSING_PARAM, Issue::TYPE_MISSING_RETURN => 'blue',
            default => 'white',
        };
    }

    private function color(string $text, string $color, bool $bold = false): string
    {
        if (!$this->useColors) {
            return $text;
        }

        $codes = [
            'black' => '30',
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
        ];

        $code = $codes[$color] ?? '37';
        $boldCode = $bold ? '1;' : '';

        return "\033[{$boldCode}{$code}m{$text}\033[0m";
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
