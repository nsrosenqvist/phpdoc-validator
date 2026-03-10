<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Formatter;

use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\Report;

/**
 * Formats validation reports as Checkstyle XML for CI tool integration.
 *
 * @see https://checkstyle.org/
 */
final class CheckstyleFormatter implements FormatterInterface
{
    public function format(Report $report, ?string $basePath = null): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('checkstyle');
        $xml->writeAttribute('version', '4.3');

        foreach ($report->getFileReports() as $fileReport) {
            $filePath = $basePath !== null
                ? $this->makeRelative($fileReport->filePath, $basePath)
                : $fileReport->filePath;

            $xml->startElement('file');
            $xml->writeAttribute('name', $filePath);

            if ($fileReport->hasParseError()) {
                $xml->startElement('error');
                $xml->writeAttribute('line', '1');
                $xml->writeAttribute('severity', 'warning');
                $xml->writeAttribute('message', $fileReport->parseError ?? 'Unknown parse error');
                $xml->writeAttribute('source', 'phpdoc-validator.parse_error');
                $xml->endElement();
            }

            foreach ($fileReport->getMethodIssues() as $data) {
                $method = $data['method'];
                $issues = $data['issues'];

                foreach ($issues as $issue) {
                    $xml->startElement('error');
                    $xml->writeAttribute('line', (string) $method->line);
                    $xml->writeAttribute('severity', $this->getSeverity($issue));
                    $xml->writeAttribute('message', $issue->message);
                    $xml->writeAttribute('source', 'phpdoc-validator.' . $issue->type);
                    $xml->endElement();
                }
            }

            $xml->endElement(); // file
        }

        $xml->endElement(); // checkstyle
        $xml->endDocument();

        return rtrim($xml->outputMemory());
    }

    private function getSeverity(Issue $issue): string
    {
        return match ($issue->type) {
            Issue::TYPE_EXTRA_PARAM => 'error',
            Issue::TYPE_TYPE_MISMATCH => 'error',
            Issue::TYPE_RETURN_MISMATCH => 'error',
            Issue::TYPE_MISSING_PARAM => 'warning',
            Issue::TYPE_MISSING_RETURN => 'warning',
            default => 'info',
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
