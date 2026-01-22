<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\TypeComparator;

use NsRosenqvist\PhpDocValidator\TypeComparator\TypeNormalizer;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeNormalizerTest extends TestCase
{
    private TypeNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TypeNormalizer(new TypeParser());
    }

    #[Test]
    public function normalizeReturnsNullForEmptyInput(): void
    {
        $this->assertNull($this->normalizer->normalize(null));
        $this->assertNull($this->normalizer->normalize(''));
        $this->assertNull($this->normalizer->normalize('   '));
    }

    #[Test]
    public function normalizeConvertsToLowercase(): void
    {
        $this->assertSame('string', $this->normalizer->normalize('String'));
        $this->assertSame('string', $this->normalizer->normalize('STRING'));
    }

    #[Test]
    public function normalizeConvertsNullableSyntax(): void
    {
        $this->assertSame('null|string', $this->normalizer->normalize('?string'));
        $this->assertSame('int|null', $this->normalizer->normalize('?int'));
    }

    #[Test]
    public function normalizeSortsUnionTypes(): void
    {
        $this->assertSame('int|string', $this->normalizer->normalize('string|int'));
        $this->assertSame('bool|int|string', $this->normalizer->normalize('string|int|bool'));
    }

    #[Test]
    public function normalizeSortsIntersectionTypes(): void
    {
        $this->assertSame('countable&iterator', $this->normalizer->normalize('Iterator&Countable'));
    }

    #[Test]
    #[DataProvider('typeAliasProvider')]
    public function normalizeAppliesTypeAliases(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function typeAliasProvider(): array
    {
        return [
            'boolean to bool' => ['boolean', 'bool'],
            'integer to int' => ['integer', 'int'],
            'double to float' => ['double', 'float'],
            'real to float' => ['real', 'float'],
            'callback to callable' => ['callback', 'callable'],
        ];
    }

    #[Test]
    public function normalizeRemovesLeadingBackslashes(): void
    {
        $this->assertSame('datetime', $this->normalizer->normalize('\\DateTime'));
        // Internal backslashes are also removed during normalization
        $this->assertSame('appmodelsuser', $this->normalizer->normalize('\\App\\Models\\User'));
    }
}
