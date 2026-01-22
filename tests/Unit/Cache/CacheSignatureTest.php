<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\Cache;

use NsRosenqvist\PhpDocValidator\Cache\CacheMode;
use NsRosenqvist\PhpDocValidator\Cache\CacheSignature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheSignature::class)]
final class CacheSignatureTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $signature = new CacheSignature(
            validatorVersion: '1.0.0',
            phpVersion: '8.2',
            reportMissing: true,
            cacheMode: CacheMode::Hash,
        );

        $this->assertSame('1.0.0', $signature->validatorVersion);
        $this->assertSame('8.2', $signature->phpVersion);
        $this->assertTrue($signature->reportMissing);
        $this->assertSame(CacheMode::Hash, $signature->cacheMode);
    }

    #[Test]
    public function matchesReturnsTrueForIdenticalSignatures(): void
    {
        $signature1 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $signature2 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);

        $this->assertTrue($signature1->matches($signature2));
    }

    #[Test]
    public function matchesReturnsFalseForDifferentValidatorVersion(): void
    {
        $signature1 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $signature2 = new CacheSignature('1.1.0', '8.2', true, CacheMode::Hash);

        $this->assertFalse($signature1->matches($signature2));
    }

    #[Test]
    public function matchesReturnsFalseForDifferentPhpVersion(): void
    {
        $signature1 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $signature2 = new CacheSignature('1.0.0', '8.3', true, CacheMode::Hash);

        $this->assertFalse($signature1->matches($signature2));
    }

    #[Test]
    public function matchesReturnsFalseForDifferentReportMissing(): void
    {
        $signature1 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $signature2 = new CacheSignature('1.0.0', '8.2', false, CacheMode::Hash);

        $this->assertFalse($signature1->matches($signature2));
    }

    #[Test]
    public function matchesReturnsFalseForDifferentCacheMode(): void
    {
        $signature1 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $signature2 = new CacheSignature('1.0.0', '8.2', true, CacheMode::Mtime);

        $this->assertFalse($signature1->matches($signature2));
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $signature = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $array = $signature->toArray();

        $this->assertArrayHasKey('cacheVersion', $array);
        $this->assertSame(1, $array['cacheVersion']);
        $this->assertSame('1.0.0', $array['validatorVersion']);
        $this->assertSame('8.2', $array['phpVersion']);
        $this->assertTrue($array['reportMissing']);
        $this->assertSame('hash', $array['cacheMode']);
    }

    #[Test]
    public function fromArrayReconstructsSignature(): void
    {
        $original = new CacheSignature('1.0.0', '8.2', true, CacheMode::Hash);
        $array = $original->toArray();
        $reconstructed = CacheSignature::fromArray($array);

        $this->assertNotNull($reconstructed);
        $this->assertTrue($original->matches($reconstructed));
    }

    #[Test]
    public function fromArrayReturnsNullForMissingCacheVersion(): void
    {
        $array = [
            'validatorVersion' => '1.0.0',
            'phpVersion' => '8.2',
            'reportMissing' => true,
            'cacheMode' => 'hash',
        ];

        $this->assertNull(CacheSignature::fromArray($array));
    }

    #[Test]
    public function fromArrayReturnsNullForWrongCacheVersion(): void
    {
        $array = [
            'cacheVersion' => 999,
            'validatorVersion' => '1.0.0',
            'phpVersion' => '8.2',
            'reportMissing' => true,
            'cacheMode' => 'hash',
        ];

        $this->assertNull(CacheSignature::fromArray($array));
    }

    #[Test]
    public function fromArrayReturnsNullForMissingRequiredFields(): void
    {
        $array = [
            'cacheVersion' => 1,
            'validatorVersion' => '1.0.0',
            // Missing phpVersion, reportMissing, cacheMode
        ];

        $this->assertNull(CacheSignature::fromArray($array));
    }

    #[Test]
    public function fromArrayReturnsNullForInvalidCacheMode(): void
    {
        $array = [
            'cacheVersion' => 1,
            'validatorVersion' => '1.0.0',
            'phpVersion' => '8.2',
            'reportMissing' => true,
            'cacheMode' => 'invalid',
        ];

        $this->assertNull(CacheSignature::fromArray($array));
    }

    #[Test]
    public function fromArrayReturnsNullForNonStringVersions(): void
    {
        $array = [
            'cacheVersion' => 1,
            'validatorVersion' => 100, // Should be string
            'phpVersion' => '8.2',
            'reportMissing' => true,
            'cacheMode' => 'hash',
        ];

        $this->assertNull(CacheSignature::fromArray($array));
    }
}
