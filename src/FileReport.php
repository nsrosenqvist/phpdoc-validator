<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Represents a validation report for a single file.
 */
final class FileReport
{
    /**
     * @var array<string, array{method: MethodInfo, issues: list<Issue>}>
     */
    private array $methodIssues = [];

    public function __construct(
        public readonly string $filePath,
        public readonly ?string $parseError = null,
    ) {}

    /**
     * @param list<Issue> $issues
     */
    public function addMethodIssues(MethodInfo $method, array $issues): void
    {
        if ($issues === []) {
            return;
        }

        $key = $method->line . ':' . $method->getFullName();
        $this->methodIssues[$key] = [
            'method' => $method,
            'issues' => $issues,
        ];
    }

    /**
     * @return array<string, array{method: MethodInfo, issues: list<Issue>}>
     */
    public function getMethodIssues(): array
    {
        return $this->methodIssues;
    }

    public function hasIssues(): bool
    {
        return $this->methodIssues !== [] || $this->parseError !== null;
    }

    public function hasParseError(): bool
    {
        return $this->parseError !== null;
    }

    public function getIssueCount(): int
    {
        $count = 0;
        foreach ($this->methodIssues as $data) {
            $count += count($data['issues']);
        }

        return $count;
    }
}
