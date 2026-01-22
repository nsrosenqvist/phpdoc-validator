<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Formatter;

use NsRosenqvist\PhpDocValidator\Report;

/**
 * Formats validation reports as JSON for tooling integration.
 */
final class JsonFormatter implements FormatterInterface
{
    public function format(Report $report, ?string $basePath = null): string
    {
        $data = [
            'summary' => [
                'filesScanned' => $report->getFilesScanned(),
                'filesWithIssues' => $report->getFilesWithIssues(),
                'totalIssues' => $report->getTotalIssues(),
                'parseErrors' => $report->getParseErrorCount(),
                'passed' => $report->isClean(),
            ],
            'files' => [],
        ];

        foreach ($report->getFileReports() as $fileReport) {
            $filePath = $basePath !== null
                ? $this->makeRelative($fileReport->filePath, $basePath)
                : $fileReport->filePath;

            $fileData = [
                'path' => $filePath,
                'parseError' => $fileReport->parseError,
                'methods' => [],
            ];

            foreach ($fileReport->getMethodIssues() as $methodData) {
                $method = $methodData['method'];
                $issues = $methodData['issues'];

                $methodEntry = [
                    'name' => $method->getFullName(),
                    'line' => $method->line,
                    'issues' => [],
                ];

                foreach ($issues as $issue) {
                    $methodEntry['issues'][] = [
                        'type' => $issue->type,
                        'param' => $issue->paramName,
                        'message' => $issue->message,
                        'expectedType' => $issue->expectedType,
                        'actualType' => $issue->actualType,
                    ];
                }

                $fileData['methods'][] = $methodEntry;
            }

            $data['files'][] = $fileData;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : '{"error": "Failed to encode JSON"}';
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
