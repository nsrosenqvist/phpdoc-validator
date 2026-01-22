<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\Parser\DocBlockParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocBlockParserTest extends TestCase
{
    private DocBlockParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DocBlockParser();
    }

    #[Test]
    public function parsesSingleParam(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name The name
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(1, $params);
        $this->assertArrayHasKey('name', $params);
        $this->assertSame('string', $params['name']);
    }

    #[Test]
    public function parsesMultipleParams(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name The name
 * @param int $age The age
 * @param bool $active Is active
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(3, $params);
        $this->assertSame('string', $params['name']);
        $this->assertSame('int', $params['age']);
        $this->assertSame('bool', $params['active']);
    }

    #[Test]
    public function parsesNullableTypes(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string|null $name Optional name
 * @param ?int $age Optional age
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(2, $params);
        $this->assertSame('string|null', $params['name']);
        $this->assertSame('?int', $params['age']);
    }

    #[Test]
    public function parsesUnionTypes(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string|int $value Mixed value
 * @param array|null $data Optional data
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(2, $params);
        $this->assertSame('string|int', $params['value']);
        $this->assertSame('array|null', $params['data']);
    }

    #[Test]
    public function parsesGenericTypes(): void
    {
        $docComment = <<<'DOC'
/**
 * @param array<string, int> $data Keyed data
 * @param list<string> $items List of items
 * @param class-string<DateTime> $class Class name
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(3, $params);
        // phpDocumentor returns generic types without spaces after commas
        // and adds backslashes to class names within generics
        $this->assertSame('array<string,int>', $params['data']);
        $this->assertSame('list<string>', $params['items']);
        $this->assertSame('class-string<\DateTime>', $params['class']);
    }

    #[Test]
    public function returnsEmptyArrayForNoParams(): void
    {
        $docComment = <<<'DOC'
/**
 * A method without parameters.
 *
 * @return void
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertSame([], $params);
    }

    #[Test]
    public function returnsEmptyArrayForMalformedDoc(): void
    {
        $docComment = 'not a valid docblock';

        $params = $this->parser->parseParams($docComment);

        $this->assertSame([], $params);
    }

    #[Test]
    public function hasParamTagsReturnsTrueWhenPresent(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name The name
 */
DOC;

        $this->assertTrue($this->parser->hasParamTags($docComment));
    }

    #[Test]
    public function hasParamTagsReturnsFalseWhenAbsent(): void
    {
        $docComment = <<<'DOC'
/**
 * A method description.
 *
 * @return void
 */
DOC;

        $this->assertFalse($this->parser->hasParamTags($docComment));
    }

    #[Test]
    public function hasParamTagsReturnsFalseForMalformedDoc(): void
    {
        $docComment = 'not a valid docblock';

        $this->assertFalse($this->parser->hasParamTags($docComment));
    }

    #[Test]
    public function parsesCallableTypes(): void
    {
        $docComment = <<<'DOC'
/**
 * @param callable(string, int): bool $callback The callback
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(1, $params);
        $this->assertArrayHasKey('callback', $params);
    }

    #[Test]
    public function parsesParamsWithoutDescription(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name
 * @param int $age
 */
DOC;

        $params = $this->parser->parseParams($docComment);

        $this->assertCount(2, $params);
        $this->assertSame('string', $params['name']);
        $this->assertSame('int', $params['age']);
    }

    #[Test]
    public function cacheIsUsedForRepeatedCalls(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name
 * @return int
 */
DOC;

        // First call - parses the doc
        $params1 = $this->parser->parseParams($docComment);

        // Second call - should use cache
        $params2 = $this->parser->parseParams($docComment);

        // Third call - different method, same doc
        $hasParams = $this->parser->hasParamTags($docComment);

        // Fourth call - return type
        $returnType = $this->parser->parseReturn($docComment);

        $this->assertSame($params1, $params2);
        $this->assertTrue($hasParams);
        $this->assertSame('int', $returnType);
    }

    #[Test]
    public function clearCacheRemovesCachedDocBlocks(): void
    {
        $docComment = <<<'DOC'
/**
 * @param string $name
 */
DOC;

        // Parse and cache
        $this->parser->parseParams($docComment);

        // Clear cache
        $this->parser->clearCache();

        // This should work fine (parses again)
        $params = $this->parser->parseParams($docComment);

        $this->assertCount(1, $params);
        $this->assertSame('string', $params['name']);
    }
}
