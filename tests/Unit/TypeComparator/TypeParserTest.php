<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\TypeComparator;

use NsRosenqvist\PhpDocValidator\TypeComparator\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeParserTest extends TestCase
{
    private TypeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TypeParser();
    }

    #[Test]
    public function parseUnionTypeHandlesSimpleTypes(): void
    {
        $this->assertSame(['string'], $this->parser->parseUnionType('string'));
        $this->assertSame(['int', 'string'], $this->parser->parseUnionType('string|int'));
        $this->assertSame(['bool', 'int', 'string'], $this->parser->parseUnionType('string|int|bool'));
    }

    #[Test]
    public function parseUnionTypeHandlesNestedGenerics(): void
    {
        $result = $this->parser->parseUnionType('array<string, int>|null');
        $this->assertSame(['array<string, int>', 'null'], $result);
    }

    #[Test]
    public function parseUnionTypeHandlesArrayShapes(): void
    {
        $result = $this->parser->parseUnionType('array{id: int, name: string}|null');
        $this->assertSame(['array{id: int, name: string}', 'null'], $result);
    }

    #[Test]
    public function parseUnionTypeHandlesNestedArrayShapes(): void
    {
        $result = $this->parser->parseUnionType('array{user: array{id: int|null}}|null');
        $this->assertSame(['array{user: array{id: int|null}}', 'null'], $result);
    }

    #[Test]
    public function stripGenericsRemovesGenericParameters(): void
    {
        $this->assertSame('array', $this->parser->stripGenerics('array<string>'));
        $this->assertSame('array', $this->parser->stripGenerics('array<string, int>'));
        $this->assertSame('Collection', $this->parser->stripGenerics('Collection<int|string, array<string, mixed>>'));
    }

    #[Test]
    public function stripGenericsRemovesArrayShapes(): void
    {
        $this->assertSame('array', $this->parser->stripGenerics('array{id: int}'));
        $this->assertSame('array', $this->parser->stripGenerics('array{user: array{id: int}}'));
    }

    #[Test]
    public function extractGenericParameterExtractsTypeParameter(): void
    {
        $this->assertSame('string', $this->parser->extractGenericParameter('array<string>'));
        $this->assertSame('string, int', $this->parser->extractGenericParameter('array<string, int>'));
        $this->assertSame('T', $this->parser->extractGenericParameter('Collection<T>'));
        $this->assertNull($this->parser->extractGenericParameter('string'));
    }

    #[Test]
    public function setsEqualComparesArraysOrderIndependently(): void
    {
        $this->assertTrue($this->parser->setsEqual(['a', 'b'], ['b', 'a']));
        $this->assertTrue($this->parser->setsEqual(['a', 'b', 'c'], ['c', 'a', 'b']));
        $this->assertFalse($this->parser->setsEqual(['a', 'b'], ['a', 'b', 'c']));
        $this->assertFalse($this->parser->setsEqual(['a'], ['b']));
    }

    #[Test]
    #[DataProvider('balancedBracketsProvider')]
    public function removeBalancedBracketsHandlesNesting(string $input, string $open, string $close, string $expected): void
    {
        $this->assertSame($expected, $this->parser->removeBalancedBrackets($input, $open, $close));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function balancedBracketsProvider(): array
    {
        return [
            'simple generics' => ['array<string>', '<', '>', 'array'],
            'nested generics' => ['array<array<int>>', '<', '>', 'array'],
            'multiple generic params' => ['Map<string, array<int>>', '<', '>', 'Map'],
            'array shape' => ['array{id: int}', '{', '}', 'array'],
            'nested array shape' => ['array{user: array{id: int}}', '{', '}', 'array'],
        ];
    }
}
