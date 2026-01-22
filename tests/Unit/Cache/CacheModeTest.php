<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\Cache;

use NsRosenqvist\PhpDocValidator\Cache\CacheMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheMode::class)]
final class CacheModeTest extends TestCase
{
    #[Test]
    public function hashModeIsEnabled(): void
    {
        $this->assertTrue(CacheMode::Hash->isEnabled());
    }

    #[Test]
    public function mtimeModeIsEnabled(): void
    {
        $this->assertTrue(CacheMode::Mtime->isEnabled());
    }

    #[Test]
    public function noneModeIsNotEnabled(): void
    {
        $this->assertFalse(CacheMode::None->isEnabled());
    }

    #[Test]
    public function hashModeReturnsContentHash(): void
    {
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';
        $key = CacheMode::Hash->getFileKey($fixture);

        $this->assertNotNull($key);
        $this->assertSame(64, strlen($key)); // SHA-256 produces 64 hex chars
    }

    #[Test]
    public function mtimeModeReturnsMtime(): void
    {
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';
        $key = CacheMode::Mtime->getFileKey($fixture);

        $this->assertNotNull($key);
        $this->assertMatchesRegularExpression('/^\d+$/', $key);
    }

    #[Test]
    public function noneModeReturnsNull(): void
    {
        $fixture = __DIR__ . '/../../fixtures/ValidClass.php';
        $key = CacheMode::None->getFileKey($fixture);

        $this->assertNull($key);
    }

    #[Test]
    public function hashModeReturnsNullForNonExistentFile(): void
    {
        $key = CacheMode::Hash->getFileKey('/nonexistent/file.php');

        $this->assertNull($key);
    }

    #[Test]
    public function mtimeModeReturnsNullForNonExistentFile(): void
    {
        $key = CacheMode::Mtime->getFileKey('/nonexistent/file.php');

        $this->assertNull($key);
    }

    #[Test]
    #[DataProvider('cacheModeCases')]
    public function tryFromReturnsCorrectMode(string $value, CacheMode $expected): void
    {
        $this->assertSame($expected, CacheMode::tryFrom($value));
    }

    /**
     * @return array<string, array{string, CacheMode}>
     */
    public static function cacheModeCases(): array
    {
        return [
            'hash' => ['hash', CacheMode::Hash],
            'mtime' => ['mtime', CacheMode::Mtime],
            'none' => ['none', CacheMode::None],
        ];
    }

    #[Test]
    public function tryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(CacheMode::tryFrom('invalid'));
    }
}
