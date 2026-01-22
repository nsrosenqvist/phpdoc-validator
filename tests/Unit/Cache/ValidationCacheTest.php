<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\Cache;

use NsRosenqvist\PhpDocValidator\Cache\CacheMode;
use NsRosenqvist\PhpDocValidator\Cache\CacheSignature;
use NsRosenqvist\PhpDocValidator\Cache\ValidationCache;
use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\IssueType;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationCache::class)]
final class ValidationCacheTest extends TestCase
{
    private string $cacheFile;

    private CacheSignature $signature;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/phpdoc-validator-test-' . uniqid() . '.cache';
        $this->signature = new CacheSignature('1.0.0', '8.2', false, CacheMode::Hash);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    #[Test]
    public function newCacheRequiresValidation(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $this->assertTrue($cache->needsValidation($fixture));
    }

    #[Test]
    public function cachedFileDoesNotRequireValidation(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $cache->setCachedResult($fixture, $fileReport);
        $cache->save();

        // Create new cache instance that loads the saved data
        $cache2 = new ValidationCache($this->cacheFile, $this->signature);

        $this->assertFalse($cache2->needsValidation($fixture));
    }

    #[Test]
    public function getCachedFileReportReturnsNullForUncachedFile(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $this->assertNull($cache->getCachedFileReport($fixture));
    }

    #[Test]
    public function getCachedFileReportReturnsCachedData(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $method = new MethodInfo(
            name: 'testMethod',
            line: 42,
            parameters: [],
            returnType: null,
            docComment: null,
            className: 'TestClass',
        );
        $issues = [
            new Issue(
                type: IssueType::TypeMismatch->value,
                paramName: '$name',
                message: 'Type mismatch',
                expectedType: 'string',
                actualType: 'int',
            ),
        ];
        $fileReport->addMethodIssues($method, $issues);
        $cache->setCachedResult($fixture, $fileReport);

        $cachedReport = $cache->getCachedFileReport($fixture);

        $this->assertNotNull($cachedReport);
        $this->assertSame($fixture, $cachedReport->filePath);
        $methodIssues = $cachedReport->getMethodIssues();
        $this->assertCount(1, $methodIssues);

        $firstEntry = array_values($methodIssues)[0];
        $this->assertSame('testMethod', $firstEntry['method']->name);
        $this->assertSame(42, $firstEntry['method']->line);
        $this->assertSame('TestClass', $firstEntry['method']->className);
        $this->assertCount(1, $firstEntry['issues']);
        $this->assertSame(IssueType::TypeMismatch, $firstEntry['issues'][0]->issueType);
    }

    #[Test]
    public function cacheIsClearedOnClear(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $cache->setCachedResult($fixture, $fileReport);
        $this->assertFalse($cache->needsValidation($fixture));

        $cache->clear();

        $this->assertTrue($cache->needsValidation($fixture));
    }

    #[Test]
    public function cacheIsInvalidatedOnSignatureMismatch(): void
    {
        $cache1 = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $cache1->setCachedResult($fixture, $fileReport);
        $cache1->save();

        // Create new cache with different signature (reportMissing changed)
        $newSignature = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $cache2 = new ValidationCache($this->cacheFile, $newSignature);

        // Should require validation due to signature mismatch
        $this->assertTrue($cache2->needsValidation($fixture));
    }

    #[Test]
    public function cacheHandlesNonExistentFile(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);

        $this->assertTrue($cache->needsValidation('/nonexistent/file.php'));
        $this->assertNull($cache->getCachedFileReport('/nonexistent/file.php'));
    }

    #[Test]
    public function cacheIsDisabledWithNoneMode(): void
    {
        $signature = new CacheSignature('1.0.0', '8.2', false, CacheMode::None);
        $cache = new ValidationCache($this->cacheFile, $signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $cache->setCachedResult($fixture, $fileReport);

        // Should always require validation when disabled
        $this->assertTrue($cache->needsValidation($fixture));
        $this->assertNull($cache->getCachedFileReport($fixture));
    }

    #[Test]
    public function cacheHandlesCorruptedFile(): void
    {
        // Write garbage to cache file
        file_put_contents($this->cacheFile, 'not valid json {{{');

        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        // Should gracefully handle corrupted file
        $this->assertTrue($cache->needsValidation($fixture));
    }

    #[Test]
    public function mtimeModeUsesModificationTime(): void
    {
        $signature = new CacheSignature('1.0.0', '8.2', false, CacheMode::Mtime);
        $cache = new ValidationCache($this->cacheFile, $signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $cache->setCachedResult($fixture, $fileReport);

        // Should not need validation since file hasn't changed
        $this->assertFalse($cache->needsValidation($fixture));
    }

    #[Test]
    public function cachePersistsBetweenInstances(): void
    {
        $cache1 = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $method = new MethodInfo(
            name: 'persistTest',
            line: 100,
            parameters: [],
            returnType: null,
            docComment: null,
            className: 'PersistClass',
        );
        $issues = [
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: '$arg',
                message: 'Missing param',
            ),
        ];
        $fileReport->addMethodIssues($method, $issues);

        $cache1->setCachedResult($fixture, $fileReport);
        $cache1->save();

        // Create new instance
        $cache2 = new ValidationCache($this->cacheFile, $this->signature);

        $cachedReport = $cache2->getCachedFileReport($fixture);

        $this->assertNotNull($cachedReport);
        $methodIssues = $cachedReport->getMethodIssues();
        $this->assertCount(1, $methodIssues);
        $firstEntry = array_values($methodIssues)[0];
        $this->assertSame(IssueType::MissingParam, $firstEntry['issues'][0]->issueType);
    }

    #[Test]
    public function getCachedFileReportPreservesAllIssueProperties(): void
    {
        $cache = new ValidationCache($this->cacheFile, $this->signature);
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';

        $fileReport = new FileReport($fixture);
        $method = new MethodInfo(
            name: 'returnMethod',
            line: 55,
            parameters: [],
            returnType: 'string',
            docComment: null,
            className: 'ReturnClass',
        );
        $issues = [
            new Issue(
                type: IssueType::ReturnMismatch->value,
                paramName: '@return',
                message: 'Return type mismatch',
                expectedType: 'array',
                actualType: 'string',
            ),
        ];
        $fileReport->addMethodIssues($method, $issues);

        $cache->setCachedResult($fixture, $fileReport);
        $cache->save();

        $cache2 = new ValidationCache($this->cacheFile, $this->signature);
        $cachedReport = $cache2->getCachedFileReport($fixture);

        $this->assertNotNull($cachedReport);
        $methodIssues = $cachedReport->getMethodIssues();
        $firstEntry = array_values($methodIssues)[0];

        $this->assertSame(IssueType::ReturnMismatch, $firstEntry['issues'][0]->issueType);
        $this->assertSame('@return', $firstEntry['issues'][0]->paramName);
        $this->assertSame('Return type mismatch', $firstEntry['issues'][0]->message);
        $this->assertSame('array', $firstEntry['issues'][0]->expectedType);
        $this->assertSame('string', $firstEntry['issues'][0]->actualType);
    }
}
