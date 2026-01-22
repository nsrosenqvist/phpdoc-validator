<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\TypeComparator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeComparatorTest extends TestCase
{
    private TypeComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new TypeComparator();
    }

    #[Test]
    public function exactMatchReturnsTrue(): void
    {
        $this->assertTrue($this->comparator->areCompatible('string', 'string'));
        $this->assertTrue($this->comparator->areCompatible('int', 'int'));
        $this->assertTrue($this->comparator->areCompatible('array', 'array'));
        $this->assertTrue($this->comparator->areCompatible('bool', 'bool'));
    }

    #[Test]
    #[DataProvider('typeAliasProvider')]
    public function typeAliasesAreCompatible(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function typeAliasProvider(): array
    {
        return [
            'int vs integer' => ['int', 'integer'],
            'integer vs int' => ['integer', 'int'],
            'bool vs boolean' => ['bool', 'boolean'],
            'boolean vs bool' => ['boolean', 'bool'],
            'float vs double' => ['float', 'double'],
            'float vs real' => ['float', 'real'],
        ];
    }

    #[Test]
    public function nullableTypesAreNormalized(): void
    {
        $this->assertTrue($this->comparator->areCompatible('?string', 'string|null'));
        $this->assertTrue($this->comparator->areCompatible('string|null', '?string'));
        $this->assertTrue($this->comparator->areCompatible('?int', 'null|int'));
    }

    #[Test]
    public function unionTypesAreOrderIndependent(): void
    {
        $this->assertTrue($this->comparator->areCompatible('string|int', 'int|string'));
        $this->assertTrue($this->comparator->areCompatible('int|string|null', 'null|string|int'));
    }

    #[Test]
    public function intersectionTypesAreOrderIndependent(): void
    {
        $this->assertTrue($this->comparator->areCompatible('Countable&Iterator', 'Iterator&Countable'));
        $this->assertTrue($this->comparator->areCompatible('A&B&C', 'C&A&B'));
    }

    #[Test]
    #[DataProvider('genericTypeProvider')]
    public function genericTypesAreCompatible(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function genericTypeProvider(): array
    {
        return [
            'array vs array<string>' => ['array', 'array<string>'],
            'array vs list<int>' => ['array', 'list<int>'],
            'array vs non-empty-array<string>' => ['array', 'non-empty-array<string>'],
            'array vs non-empty-list<string>' => ['array', 'non-empty-list<string>'],
            'array vs Type[] syntax' => ['array', 'string[]'],
            'array vs FQCN[] syntax' => ['array', '\\Tag[]'],
            'array vs nested[] syntax' => ['array', 'array<string, int>[]'],
            'string vs class-string' => ['string', 'class-string'],
            'string vs class-string<DateTime>' => ['string', 'class-string<DateTime>'],
            'iterable vs iterable<string>' => ['iterable', 'iterable<string>'],
            'int vs positive-int' => ['int', 'positive-int'],
            'int vs negative-int' => ['int', 'negative-int'],
            'int vs non-negative-int' => ['int', 'non-negative-int'],
            'string vs numeric-string' => ['string', 'numeric-string'],
            'string vs callable-string' => ['string', 'callable-string'],
        ];
    }

    #[Test]
    public function closureAndCallableAreCompatible(): void
    {
        $this->assertTrue($this->comparator->areCompatible('callable', '\\Closure'));
        $this->assertTrue($this->comparator->areCompatible('Closure', 'callable'));
    }

    #[Test]
    #[DataProvider('incompatibleTypeProvider')]
    public function incompatibleTypesReturnFalse(string $actual, string $doc): void
    {
        $this->assertFalse($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function incompatibleTypeProvider(): array
    {
        return [
            'string vs int' => ['string', 'int'],
            'int vs string' => ['int', 'string'],
            'array vs string' => ['array', 'string'],
            'bool vs string' => ['bool', 'string'],
            'DateTime vs string' => ['DateTime', 'string'],
            'float vs bool' => ['float', 'bool'],
        ];
    }

    #[Test]
    public function normalizeReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->comparator->normalize(''));
        $this->assertNull($this->comparator->normalize('   '));
        $this->assertNull($this->comparator->normalize(null));
    }

    #[Test]
    public function normalizeConvertsToLowercase(): void
    {
        $this->assertSame('string', $this->comparator->normalize('String'));
        $this->assertSame('string', $this->comparator->normalize('STRING'));
        $this->assertSame('datetime', $this->comparator->normalize('DateTime'));
    }

    #[Test]
    public function normalizeSortsUnionTypes(): void
    {
        $this->assertSame('int|string', $this->comparator->normalize('string|int'));
        $this->assertSame('bool|int|string', $this->comparator->normalize('string|int|bool'));
    }

    #[Test]
    #[DataProvider('objectCompatibilityProvider')]
    public function objectSignatureAcceptsClassNames(string $actual, string $doc, bool $expected): void
    {
        $this->assertSame($expected, $this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function objectCompatibilityProvider(): array
    {
        return [
            // object signature + class name doc = compatible (doc is more specific)
            'object vs MyClass' => ['object', 'MyClass', true],
            'object vs DateTime' => ['object', 'DateTime', true],
            'object vs \Fully\Qualified\ClassName' => ['object', '\Fully\Qualified\ClassName', true],
            'object vs MyClass<T>' => ['object', 'MyClass<T>', true],
            'object vs Generic<OtherClass>' => ['object', 'Generic<OtherClass>', true],

            // class name signature + object doc = not compatible (doc loses specificity)
            'MyClass vs object' => ['MyClass', 'object', false],
            'DateTime vs object' => ['DateTime', 'object', false],

            // object vs native types = not compatible
            'object vs string' => ['object', 'string', false],
            'object vs int' => ['object', 'int', false],
            'object vs array' => ['object', 'array', false],

            // object vs object = compatible (exact match)
            'object vs object' => ['object', 'object', true],
        ];
    }
}
