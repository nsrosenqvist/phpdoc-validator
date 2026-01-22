<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

use FilesystemIterator;
use NsRosenqvist\PhpDocValidator\Cache\ValidationCache;
use NsRosenqvist\PhpDocValidator\Parser\DocBlockParser;
use NsRosenqvist\PhpDocValidator\Parser\MethodVisitor;
use NsRosenqvist\PhpDocValidator\Validator\MethodValidator;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Main orchestrator for PHPDoc parameter validation.
 */
final class PhpDocValidator
{
    private \PhpParser\Parser $parser;

    private MethodVisitor $methodVisitor;

    private MethodValidator $methodValidator;

    private DocBlockParser $docBlockParser;

    private ?ValidationCache $cache = null;

    /**
     * @var list<string>
     */
    private array $excludePatterns = [];

    private bool $reportMissing = false;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->methodVisitor = new MethodVisitor();
        $this->docBlockParser = new DocBlockParser();
        $this->methodValidator = new MethodValidator(
            $this->docBlockParser,
            new TypeComparator(),
        );
    }

    /**
     * Set patterns to exclude from scanning.
     *
     * @param list<string> $patterns Glob-style patterns
     */
    public function setExcludePatterns(array $patterns): self
    {
        $this->excludePatterns = $patterns;

        return $this;
    }

    /**
     * Enable reporting of missing @param documentation.
     */
    public function setReportMissing(bool $report): self
    {
        $this->reportMissing = $report;

        return $this;
    }

    /**
     * Set the validation cache instance.
     */
    public function setCache(?ValidationCache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Validate PHPDoc parameters in the given paths.
     *
     * @param list<string> $paths Directories or files to scan
     */
    public function validate(array $paths): Report
    {
        $report = new Report();

        foreach ($paths as $path) {
            if (is_file($path)) {
                $report->addFileReport($this->validateFile($path));
            } elseif (is_dir($path)) {
                $this->validateDirectory($path, $report);
            }
        }

        // Save cache after validation completes
        $this->cache?->save();

        return $report;
    }

    /**
     * Validate a single file.
     */
    public function validateFile(string $filePath): FileReport
    {
        // Check if we can use cached results
        if ($this->cache !== null && !$this->cache->needsValidation($filePath)) {
            $cachedReport = $this->cache->getCachedFileReport($filePath);
            if ($cachedReport !== null) {
                return $cachedReport;
            }
        }

        $content = @file_get_contents($filePath);

        if ($content === false) {
            return new FileReport($filePath, "Could not read file: {$filePath}");
        }

        $fileReport = $this->validateContent($content, $filePath);

        // Cache the results
        $this->cache?->setCachedResult($filePath, $fileReport);

        return $fileReport;
    }

    /**
     * Validate PHP content from a string.
     */
    public function validateContent(string $content, string $filePath = 'php://input'): FileReport
    {
        try {
            $ast = $this->parser->parse($content);

            if ($ast === null) {
                return new FileReport($filePath, 'Failed to parse PHP content');
            }
        } catch (\PhpParser\Error $e) {
            return new FileReport($filePath, 'Parse error: ' . $e->getMessage());
        }

        $this->methodVisitor->reset();
        $this->docBlockParser->clearCache();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->methodVisitor);
        $traverser->traverse($ast);

        $fileReport = new FileReport($filePath);
        $methods = $this->methodVisitor->getMethods();

        foreach ($methods as $method) {
            $issues = $this->methodValidator->validate($method, $this->reportMissing);
            $fileReport->addMethodIssues($method, $issues);
        }

        return $fileReport;
    }

    private function validateDirectory(string $directory, Report $report): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();

            if ($this->shouldExclude($filePath)) {
                continue;
            }

            $report->addFileReport($this->validateFile($filePath));
        }
    }

    private function shouldExclude(string $filePath): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $filePath) || fnmatch($pattern, basename($filePath))) {
                return true;
            }
        }

        return false;
    }
}
