<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Cache;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\MethodInfo;

/**
 * Caches validation results to speed up incremental runs.
 *
 * The cache stores a signature (version info + config) and per-file results.
 * Files are only re-validated if their content/mtime has changed.
 */
final class ValidationCache
{
    public const DEFAULT_CACHE_FILE = '.phpdoc-validator.cache';

    private CacheSignature $signature;

    private CacheMode $mode;

    /**
     * @var array<string, array{key: string, methodIssues: list<array{method: array{name: string, line: int, className: ?string}, issues: list<array{type: string, paramName: string, message: string, expectedType: ?string, actualType: ?string}>}>}>
     */
    private array $files = [];

    private bool $dirty = false;

    public function __construct(
        private readonly string $cacheFile,
        CacheSignature $signature,
    ) {
        $this->signature = $signature;
        $this->mode = $signature->cacheMode;
        $this->load();
    }

    /**
     * Check if a file needs validation (not in cache or changed).
     */
    public function needsValidation(string $filePath): bool
    {
        if (!$this->mode->isEnabled()) {
            return true;
        }

        $absolutePath = $this->normalizePath($filePath);

        if (!isset($this->files[$absolutePath])) {
            return true;
        }

        $currentKey = $this->mode->getFileKey($filePath);
        if ($currentKey === null) {
            return true;
        }

        return $this->files[$absolutePath]['key'] !== $currentKey;
    }

    /**
     * Get cached FileReport for a file.
     */
    public function getCachedFileReport(string $filePath): ?FileReport
    {
        if (!$this->mode->isEnabled()) {
            return null;
        }

        $absolutePath = $this->normalizePath($filePath);

        if (!isset($this->files[$absolutePath])) {
            return null;
        }

        $fileData = $this->files[$absolutePath];
        if (!isset($fileData['methodIssues']) || !is_array($fileData['methodIssues'])) {
            return null;
        }

        $fileReport = new FileReport($filePath);

        foreach ($fileData['methodIssues'] as $methodData) {
            if (!isset($methodData['method'], $methodData['issues'])) {
                continue;
            }

            $method = new MethodInfo(
                name: $methodData['method']['name'] ?? 'unknown',
                line: $methodData['method']['line'] ?? 0,
                parameters: [],
                returnType: null,
                docComment: null,
                className: $methodData['method']['className'] ?? null,
            );

            $issues = [];
            foreach ($methodData['issues'] as $issueData) {
                $issues[] = new Issue(
                    type: $issueData['type'] ?? 'unknown',
                    paramName: $issueData['paramName'] ?? '',
                    message: $issueData['message'] ?? '',
                    expectedType: $issueData['expectedType'] ?? null,
                    actualType: $issueData['actualType'] ?? null,
                );
            }

            $fileReport->addMethodIssues($method, $issues);
        }

        return $fileReport;
    }

    /**
     * Store validation results for a file.
     */
    public function setCachedResult(string $filePath, FileReport $fileReport): void
    {
        if (!$this->mode->isEnabled()) {
            return;
        }

        $absolutePath = $this->normalizePath($filePath);
        $key = $this->mode->getFileKey($filePath);

        if ($key === null) {
            return;
        }

        // Serialize method issues
        $serializedMethodIssues = [];
        foreach ($fileReport->getMethodIssues() as $data) {
            $method = $data['method'];
            $issues = $data['issues'];

            $serializedIssues = [];
            foreach ($issues as $issue) {
                $serializedIssues[] = [
                    'type' => $issue->type,
                    'paramName' => $issue->paramName,
                    'message' => $issue->message,
                    'expectedType' => $issue->expectedType,
                    'actualType' => $issue->actualType,
                ];
            }

            $serializedMethodIssues[] = [
                'method' => [
                    'name' => $method->name,
                    'line' => $method->line,
                    'className' => $method->className,
                ],
                'issues' => $serializedIssues,
            ];
        }

        $this->files[$absolutePath] = [
            'key' => $key,
            'methodIssues' => $serializedMethodIssues,
        ];

        $this->dirty = true;
    }

    /**
     * Clear all cached data.
     */
    public function clear(): void
    {
        $this->files = [];
        $this->dirty = true;

        if (file_exists($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
    }

    /**
     * Save the cache to disk if it has been modified.
     */
    public function save(): void
    {
        if (!$this->dirty) {
            return;
        }

        $data = [
            'signature' => $this->signature->toArray(),
            'files' => $this->files,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json !== false) {
            @file_put_contents($this->cacheFile, $json);
        }

        $this->dirty = false;
    }

    private function load(): void
    {
        if (!file_exists($this->cacheFile)) {
            return;
        }

        $content = @file_get_contents($this->cacheFile);
        if ($content === false) {
            return;
        }

        try {
            /** @var array<string, mixed>|null $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Invalid cache file, start fresh
            return;
        }

        if (!is_array($data) || !isset($data['signature'], $data['files'])) {
            return;
        }

        /** @var array<string, mixed> $signatureData */
        $signatureData = $data['signature'];

        $storedSignature = CacheSignature::fromArray($signatureData);
        if ($storedSignature === null || !$this->signature->matches($storedSignature)) {
            // Signature mismatch, invalidate cache
            return;
        }

        if (is_array($data['files'])) {
            /** @var array<string, array{key: string, methodIssues: list<array{method: array{name: string, line: int, className: ?string}, issues: list<array{type: string, paramName: string, message: string, expectedType: ?string, actualType: ?string}>}>}> $files */
            $files = $data['files'];
            $this->files = $files;
        }
    }

    private function normalizePath(string $filePath): string
    {
        $realPath = realpath($filePath);

        return $realPath !== false ? $realPath : $filePath;
    }
}
