<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Fixer;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\IssueType;
use NsRosenqvist\PhpDocValidator\MethodInfo;

/**
 * Fixes PHPDoc issues in source files.
 *
 * Supports fixing:
 * - Missing @param tags (generates from signature)
 * - Missing @return tags (generates from signature)
 * - Parameter order (reorders @param tags to match signature)
 */
final class DocBlockFixer
{
    /**
     * Apply fixes to a file based on the report.
     *
     * @return int Number of fixes applied
     */
    public function fix(FileReport $report, bool $fixMissing): int
    {
        $filePath = $report->filePath;

        if (!file_exists($filePath) || !is_readable($filePath) || !is_writable($filePath)) {
            return 0;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return 0;
        }

        $fixCount = 0;
        $lines = explode("\n", $content);

        // Process methods in reverse line order to preserve line numbers
        $methodIssues = $report->getMethodIssues();
        uasort($methodIssues, fn($a, $b) => $b['method']->line <=> $a['method']->line);

        foreach ($methodIssues as $data) {
            $method = $data['method'];
            $issues = $data['issues'];

            $fixableIssues = $this->getFixableIssues($issues, $fixMissing);
            if ($fixableIssues === []) {
                continue;
            }

            $fixed = $this->fixMethod($lines, $method, $fixableIssues);
            $fixCount += $fixed;
        }

        if ($fixCount > 0) {
            file_put_contents($filePath, implode("\n", $lines));
        }

        return $fixCount;
    }

    /**
     * Filter issues to only those that can be fixed.
     *
     * @param list<Issue> $issues
     * @return list<Issue>
     */
    private function getFixableIssues(array $issues, bool $fixMissing): array
    {
        return array_values(array_filter($issues, function (Issue $issue) use ($fixMissing): bool {
            if ($issue->issueType === IssueType::ParamOrder) {
                return true; // Always fix param order
            }

            if ($fixMissing && $issue->issueType->isMissing()) {
                return true;
            }

            return false;
        }));
    }

    /**
     * Fix issues for a single method.
     *
     * @param list<string> $lines File lines (modified in place)
     * @param list<Issue> $issues
     * @return int Number of fixes applied
     */
    private function fixMethod(array &$lines, MethodInfo $method, array $issues): int
    {
        $fixCount = 0;

        // Categorize issues
        $hasParamOrderIssue = false;
        $missingParams = [];
        $hasMissingReturn = false;

        foreach ($issues as $issue) {
            if ($issue->issueType === IssueType::ParamOrder) {
                $hasParamOrderIssue = true;
            } elseif ($issue->issueType === IssueType::MissingParam) {
                $missingParams[] = $issue->paramName;
            } elseif ($issue->issueType === IssueType::MissingReturn) {
                $hasMissingReturn = true;
            }
        }

        // Find the docblock position (if exists)
        $docInfo = $this->findDocBlock($lines, $method->line);

        if ($method->hasDocComment() && $docInfo !== null) {
            // Fix existing docblock
            if ($hasParamOrderIssue) {
                $this->fixParamOrder($lines, $docInfo, $method);
                $fixCount++;
            }

            if ($missingParams !== []) {
                $this->addMissingParams($lines, $docInfo, $method, $missingParams);
                $fixCount += count($missingParams);
            }

            if ($hasMissingReturn) {
                $this->addMissingReturn($lines, $docInfo, $method);
                $fixCount++;
            }
        } else {
            // Create new docblock
            if ($missingParams !== [] || $hasMissingReturn) {
                $this->createDocBlock($lines, $method, $missingParams, $hasMissingReturn);
                $fixCount += count($missingParams) + ($hasMissingReturn ? 1 : 0);
            }
        }

        return $fixCount;
    }

    /**
     * Find the docblock for a method.
     *
     * @param list<string> $lines
     * @return array{start: int, end: int, indent: string}|null
     */
    private function findDocBlock(array $lines, int $methodLine): ?array
    {
        // Method line is 1-based, array is 0-based
        $methodIndex = $methodLine - 1;

        // Search backwards for docblock end
        $searchIndex = $methodIndex - 1;
        while ($searchIndex >= 0 && trim($lines[$searchIndex]) === '') {
            $searchIndex--;
        }

        if ($searchIndex < 0) {
            return null;
        }

        $line = trim($lines[$searchIndex]);
        if (!str_ends_with($line, '*/')) {
            return null;
        }

        $endIndex = $searchIndex;

        // Find the start of the docblock
        while ($searchIndex >= 0) {
            $line = trim($lines[$searchIndex]);
            if (str_starts_with($line, '/**')) {
                // Get indentation from the opening line
                preg_match('/^(\s*)/', $lines[$searchIndex], $matches);
                $indent = $matches[1] ?? '';

                return [
                    'start' => $searchIndex,
                    'end' => $endIndex,
                    'indent' => $indent,
                ];
            }
            $searchIndex--;
        }

        return null;
    }

    /**
     * Reorder @param tags to match method signature order.
     *
     * @param list<string> $lines
     * @param array{start: int, end: int, indent: string} $docInfo
     */
    private function fixParamOrder(array &$lines, array $docInfo, MethodInfo $method): void
    {
        $docLines = array_slice($lines, $docInfo['start'], $docInfo['end'] - $docInfo['start'] + 1);
        $indent = $docInfo['indent'];

        // Extract param lines and other lines
        $paramLines = [];
        $otherLines = [];
        $returnLine = null;
        $lastNonParamIndex = 0;

        foreach ($docLines as $index => $line) {
            $trimmed = trim($line);

            // Match @param lines
            if (preg_match('/^\*\s*@param\s+\S+\s+\$(\w+)/', $trimmed, $matches)) {
                $paramLines[$matches[1]] = $line;
            } elseif (preg_match('/^\*\s*@return\s/', $trimmed)) {
                $returnLine = $line;
            } else {
                $otherLines[$index] = $line;
                if ($trimmed !== '' && $trimmed !== '/**' && $trimmed !== '*/') {
                    $lastNonParamIndex = $index;
                }
            }
        }

        // Rebuild the docblock with params in correct order
        $newDocLines = [];

        // Add everything up to where params should go
        foreach ($otherLines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '*/') {
                break;
            }
            $newDocLines[] = $line;
        }

        // Add params in signature order
        $signatureOrder = array_keys($method->parameters);
        foreach ($signatureOrder as $paramName) {
            if (isset($paramLines[$paramName])) {
                $newDocLines[] = $paramLines[$paramName];
            }
        }

        // Add return line if present
        if ($returnLine !== null) {
            $newDocLines[] = $returnLine;
        }

        // Add closing
        $newDocLines[] = $indent . ' */';

        // Replace the docblock lines
        array_splice($lines, $docInfo['start'], $docInfo['end'] - $docInfo['start'] + 1, $newDocLines);
    }

    /**
     * Add missing @param tags to an existing docblock.
     *
     * @param list<string> $lines
     * @param array{start: int, end: int, indent: string} $docInfo
     * @param list<string> $missingParams
     */
    private function addMissingParams(array &$lines, array $docInfo, MethodInfo $method, array $missingParams): void
    {
        $indent = $docInfo['indent'];

        // Find insertion point (before @return or before closing)
        $insertIndex = $docInfo['end'];
        for ($i = $docInfo['start']; $i <= $docInfo['end']; $i++) {
            $trimmed = trim($lines[$i]);
            if (str_starts_with($trimmed, '* @return') || $trimmed === '*/') {
                $insertIndex = $i;
                break;
            }
        }

        // Generate param lines in signature order
        $newLines = [];
        $signatureOrder = array_keys($method->parameters);

        foreach ($signatureOrder as $paramName) {
            if (in_array($paramName, $missingParams, true)) {
                $type = $method->parameters[$paramName] ?? 'mixed';
                $newLines[] = $indent . " * @param {$type} \${$paramName}";
            }
        }

        // Insert the new lines
        array_splice($lines, $insertIndex, 0, $newLines);
    }

    /**
     * Add missing @return tag to an existing docblock.
     *
     * @param list<string> $lines
     * @param array{start: int, end: int, indent: string} $docInfo
     */
    private function addMissingReturn(array &$lines, array $docInfo, MethodInfo $method): void
    {
        if ($method->returnType === null) {
            return;
        }

        $indent = $docInfo['indent'];

        // Find insertion point (before closing */)
        $insertIndex = $docInfo['end'];

        $newLine = $indent . " * @return {$method->returnType}";

        array_splice($lines, $insertIndex, 0, [$newLine]);
    }

    /**
     * Create a new docblock for a method.
     *
     * @param list<string> $lines
     * @param list<string> $missingParams
     */
    private function createDocBlock(array &$lines, MethodInfo $method, array $missingParams, bool $addReturn): void
    {
        // Method line is 1-based, array is 0-based
        $methodIndex = $method->line - 1;

        // Get indentation from the method line
        preg_match('/^(\s*)/', $lines[$methodIndex], $matches);
        $indent = $matches[1] ?? '';

        $docLines = [];
        $docLines[] = $indent . '/**';

        // Add params in signature order
        $signatureOrder = array_keys($method->parameters);
        foreach ($signatureOrder as $paramName) {
            if (in_array($paramName, $missingParams, true)) {
                $type = $method->parameters[$paramName] ?? 'mixed';
                $docLines[] = $indent . " * @param {$type} \${$paramName}";
            }
        }

        // Add return
        if ($addReturn && $method->returnType !== null) {
            $docLines[] = $indent . " * @return {$method->returnType}";
        }

        $docLines[] = $indent . ' */';

        // Insert before the method
        array_splice($lines, $methodIndex, 0, $docLines);
    }
}
