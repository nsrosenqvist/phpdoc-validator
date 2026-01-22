<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\TypeComparator;

use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeClassifierTest extends TestCase
{
    private TypeClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new TypeClassifier();
    }

    #[Test]
    #[DataProvider('classNameProvider')]
    public function isClassNameIdentifiesClassNames(string $type, bool $expected): void
    {
        $this->assertSame($expected, $this->classifier->isClassName($type));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function classNameProvider(): array
    {
        return [
            'DateTime is class' => ['DateTime', true],
            'MyClass is class' => ['MyClass', true],
            'string is not class' => ['string', false],
            'int is not class' => ['int', false],
            'array is not class' => ['array', false],
            'mixed is not class' => ['mixed', false],
            'null is not class' => ['null', false],
            'void is not class' => ['void', false],
        ];
    }

    #[Test]
    #[DataProvider('templateTypeProvider')]
    public function isTemplateTypeIdentifiesTemplateTypes(string $type, bool $expected): void
    {
        $this->assertSame($expected, $this->classifier->isTemplateType($type));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function templateTypeProvider(): array
    {
        return [
            'T is template' => ['T', true],
            'TValue is template' => ['TValue', true],
            'TKey is template' => ['TKey', true],
            'T1 is template' => ['T1', true],
            'lowercase t is not template' => ['t', false],
            'string is not template' => ['string', false],
            'FQCN is not template' => ['App\\Models\\User', false],
        ];
    }

    #[Test]
    #[DataProvider('stringLiteralProvider')]
    public function isStringLiteralIdentifiesStringLiterals(string $type, bool $expected): void
    {
        $this->assertSame($expected, $this->classifier->isStringLiteral($type));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function stringLiteralProvider(): array
    {
        return [
            'double quoted' => ['"hello"', true],
            'single quoted' => ["'hello'", true],
            'double quoted with space' => ['"hello world"', true],
            'empty double quoted' => ['""', true],
            'not quoted' => ['hello', false],
            'partial quote' => ['"hello', false],
            'string type' => ['string', false],
        ];
    }

    #[Test]
    #[DataProvider('intLiteralProvider')]
    public function isIntLiteralIdentifiesIntegerLiterals(string $type, bool $expected): void
    {
        $this->assertSame($expected, $this->classifier->isIntLiteral($type));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function intLiteralProvider(): array
    {
        return [
            'zero' => ['0', true],
            'positive int' => ['42', true],
            'negative int' => ['-1', true],
            'large number' => ['12345', true],
            'not a number' => ['abc', false],
            'float' => ['3.14', false],
            'int type' => ['int', false],
        ];
    }

    #[Test]
    #[DataProvider('nativeTypeProvider')]
    public function isNativeTypeIdentifiesNativeTypes(string $type, bool $expected): void
    {
        $this->assertSame($expected, $this->classifier->isNativeType($type));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function nativeTypeProvider(): array
    {
        return [
            'string' => ['string', true],
            'int' => ['int', true],
            'float' => ['float', true],
            'bool' => ['bool', true],
            'array' => ['array', true],
            'object' => ['object', true],
            'mixed' => ['mixed', true],
            'null' => ['null', true],
            'void' => ['void', true],
            'never' => ['never', true],
            'DateTime' => ['DateTime', false],
            'MyClass' => ['MyClass', false],
        ];
    }
}
