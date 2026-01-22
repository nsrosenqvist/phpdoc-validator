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
            'string vs non-empty-string' => ['string', 'non-empty-string'],
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

    #[Test]
    #[DataProvider('arrayShapeProvider')]
    public function arrayShapesAreCompatibleWithArray(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function arrayShapeProvider(): array
    {
        return [
            'array vs simple shape' => ['array', 'array{theme: string}'],
            'nullable array vs nullable shape' => ['?array', 'array{theme: string}|null'],
            'array vs complex shape' => ['array', 'array{id: int, name: string, active: bool}'],
            'array vs nested shape' => ['array', 'array{user: array{id: int, name: string}}'],
            'nullable array vs complex nullable shape' => [
                '?array',
                'array{pr_number: int|null, title: string|null}|null',
            ],
            'array vs shape with optional keys' => ['array', 'array{required: string, optional?: int}'],
        ];
    }

    #[Test]
    #[DataProvider('stringLiteralProvider')]
    public function stringLiteralUnionsAreCompatibleWithString(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stringLiteralProvider(): array
    {
        return [
            'string vs double-quoted literal union' => ['string', '"low"|"medium"|"high"'],
            'string vs single-quoted literal union' => ['string', "'low'|'medium'|'high'"],
            'string vs two literals' => ['string', '"yes"|"no"'],
            'string vs single literal' => ['string', '"only"'],
            'string vs color literals' => ['string', '"green"|"yellow"|"red"|"neutral"'],
        ];
    }

    #[Test]
    #[DataProvider('templateTypeProvider')]
    public function templateTypesAreCompatibleWithMixed(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function templateTypeProvider(): array
    {
        return [
            'mixed vs T' => ['mixed', 'T'],
            'mixed vs TValue' => ['mixed', 'TValue'],
            'mixed vs TKey' => ['mixed', 'TKey'],
            'mixed vs TReturn' => ['mixed', 'TReturn'],
        ];
    }

    #[Test]
    #[DataProvider('genericClassTypeProvider')]
    public function genericClassTypesAreCompatibleWithBaseClass(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function genericClassTypeProvider(): array
    {
        return [
            'Generator vs Generator with type params' => [
                'Generator',
                'Generator<int, string>',
            ],
            'Collection vs Collection with type params' => [
                'Collection',
                'Collection<int|string, array<string, mixed>>',
            ],
            'iterable vs iterable with complex types' => [
                'iterable',
                'iterable<FileNode|CommitNode|Model>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('nullableClassStringProvider')]
    public function nullableClassStringIsCompatibleWithNullableString(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nullableClassStringProvider(): array
    {
        return [
            'nullable string vs class-string|null' => ['?string', 'class-string|null'],
            'nullable string vs non-empty-string|null' => ['?string', 'non-empty-string|null'],
            'string|null vs class-string|null' => ['string|null', 'class-string|null'],
        ];
    }

    #[Test]
    #[DataProvider('listTypeProvider')]
    public function listTypesAreCompatibleWithArray(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function listTypeProvider(): array
    {
        return [
            'array vs list of class union' => ['array', 'list<SystemMessage|UserMessage|AssistantMessage>'],
            'array vs list of objects' => ['array', 'list<object>'],
            'array vs list of arrays' => ['array', 'list<array{id: int, name: string}>'],
        ];
    }

    #[Test]
    #[DataProvider('newPhpDocTypesProvider')]
    public function newPhpDocTypesAreCompatible(string $actual, string $doc): void
    {
        $this->assertTrue($this->comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function newPhpDocTypesProvider(): array
    {
        return [
            // Int range and mask types
            'int vs int<0, 100>' => ['int', 'int<0, 100>'],
            'int vs int<min, max>' => ['int', 'int<min, max>'],
            'int vs int-mask<1, 2, 4>' => ['int', 'int-mask<1, 2, 4>'],
            'int vs int-mask-of<MyClass::CONST_*>' => ['int', 'int-mask-of<MyClass::CONST_*>'],
            'int vs integer literal 0' => ['int', '0'],
            'int vs integer literal 42' => ['int', '42'],
            'int vs integer literal -1' => ['int', '-1'],

            // Additional string types
            'string vs literal-string' => ['string', 'literal-string'],
            'string vs lowercase-string' => ['string', 'lowercase-string'],
            'string vs truthy-string' => ['string', 'truthy-string'],
            'string vs non-falsy-string' => ['string', 'non-falsy-string'],
            'string vs trait-string' => ['string', 'trait-string'],
            'string vs interface-string' => ['string', 'interface-string'],

            // Callable with signature
            'callable vs callable(string): void' => ['callable', 'callable(string): void'],
            'callable vs callable(int, string): bool' => ['callable', 'callable(int, string): bool'],

            // key-of and value-of
            'string vs key-of<array>' => ['string', 'key-of<array<string, mixed>>'],
            'int vs key-of<list>' => ['int', 'key-of<list<string>>'],

            // Generic class matching
            'Generator vs Generator<int, string>' => ['Generator', 'Generator<int, string>'],
            'ArrayIterator vs ArrayIterator<string>' => ['ArrayIterator', 'ArrayIterator<string>'],

            // array-key
            'string|int vs array-key' => ['string|int', 'array-key'],
            'int|string vs array-key' => ['int|string', 'array-key'],

            // scalar
            'int|float|string|bool vs scalar' => ['int|float|string|bool', 'scalar'],
            'bool|float|int|string vs scalar' => ['bool|float|int|string', 'scalar'],

            // numeric
            'int|float vs numeric' => ['int|float', 'numeric'],
            'float|int vs numeric' => ['float|int', 'numeric'],

            // callable-array
            'array vs callable-array' => ['array', 'callable-array'],

            // object shapes
            'object vs object{prop: string}' => ['object', 'object{prop: string}'],
            'object vs object{id: int, name: string}' => ['object', 'object{id: int, name: string}'],

            // never aliases
            'never vs never-return' => ['never', 'never-return'],
            'never vs no-return' => ['never', 'no-return'],
            'never vs noreturn' => ['never', 'noreturn'],

            // resource types
            'resource vs closed-resource' => ['resource', 'closed-resource'],
            'resource vs open-resource' => ['resource', 'open-resource'],
        ];
    }

    #[Test]
    #[DataProvider('customRulesProvider')]
    public function customRulesCanBeInjected(string $actual, string $doc, bool $expected): void
    {
        // Create comparator with no rules
        $comparator = new TypeComparator([]);

        // Without rules, only exact matches or base type matches should work
        $this->assertSame($expected, $comparator->areCompatible($actual, $doc));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function customRulesProvider(): array
    {
        return [
            'exact match still works' => ['string', 'string', true],
            // Base types match (array === array after stripping generics)
            'array vs array<T> works via base type' => ['array', 'array<string>', true],
            // These require rules to match
            'string vs class-string fails without rule' => ['string', 'class-string', false],
            'int vs positive-int fails without rule' => ['int', 'positive-int', false],
            'object vs MyClass fails without rule' => ['object', 'MyClass', false],
        ];
    }
}
