<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Parser;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Context;

/**
 * Parses @param and @return tags from PHPDoc blocks using phpDocumentor.
 *
 * Includes caching to avoid parsing the same docblock multiple times.
 */
final class DocBlockParser
{
    private DocBlockFactoryInterface $factory;

    /**
     * Cache of parsed docblocks keyed by their content hash.
     *
     * @var array<string, DocBlock|null>
     */
    private array $cache = [];

    public function __construct()
    {
        $this->factory = DocBlockFactory::createInstance();
    }

    /**
     * Clear the internal cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Extract @param tags from a doc comment.
     *
     * @param string $docComment The PHPDoc comment string
     * @return array<string, string|null> Parameter names mapped to their documented types
     */
    public function parseParams(string $docComment): array
    {
        $params = [];
        $docBlock = $this->getDocBlock($docComment);

        if ($docBlock === null) {
            return [];
        }

        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $tag) {
            if (!$tag instanceof Param) {
                continue;
            }

            $varName = $tag->getVariableName();
            if ($varName === null || $varName === '') {
                continue;
            }

            $type = $tag->getType();
            $params[$varName] = $type !== null ? (string) $type : null;
        }

        return $params;
    }

    /**
     * Extract @return type from a doc comment.
     *
     * @param string $docComment The PHPDoc comment string
     * @return string|null The documented return type, or null if not present
     */
    public function parseReturn(string $docComment): ?string
    {
        $docBlock = $this->getDocBlock($docComment);

        if ($docBlock === null) {
            return null;
        }

        $returnTags = $docBlock->getTagsByName('return');

        foreach ($returnTags as $tag) {
            if (!$tag instanceof Return_) {
                continue;
            }

            $type = $tag->getType();

            return $type !== null ? (string) $type : null;
        }

        return null;
    }

    /**
     * Check if a doc comment has any @param tags.
     */
    public function hasParamTags(string $docComment): bool
    {
        $docBlock = $this->getDocBlock($docComment);

        if ($docBlock === null) {
            return false;
        }

        return count($docBlock->getTagsByName('param')) > 0;
    }

    /**
     * Check if a doc comment has a @return tag.
     */
    public function hasReturnTag(string $docComment): bool
    {
        $docBlock = $this->getDocBlock($docComment);

        if ($docBlock === null) {
            return false;
        }

        return count($docBlock->getTagsByName('return')) > 0;
    }

    /**
     * Get the ordered list of parameter names from @param tags.
     *
     * @return list<string>
     */
    public function getParamOrder(string $docComment): array
    {
        $docBlock = $this->getDocBlock($docComment);

        if ($docBlock === null) {
            return [];
        }

        $paramTags = $docBlock->getTagsByName('param');
        $order = [];

        foreach ($paramTags as $tag) {
            if (!$tag instanceof Param) {
                continue;
            }

            $varName = $tag->getVariableName();
            if ($varName !== null && $varName !== '') {
                $order[] = $varName;
            }
        }

        return $order;
    }

    /**
     * Get or create a cached DocBlock instance for a doc comment.
     */
    private function getDocBlock(string $docComment): ?DocBlock
    {
        $cacheKey = md5($docComment);

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        try {
            $docBlock = $this->factory->create($docComment, new Context(''));
            $this->cache[$cacheKey] = $docBlock;

            return $docBlock;
        } catch (\Exception) {
            $this->cache[$cacheKey] = null;

            return null;
        }
    }
}
