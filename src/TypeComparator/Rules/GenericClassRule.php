<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles class-string type compatibility with generic classes.
 *
 * Supports:
 * - MyClass vs MyClass<T> (generic class with base class)
 * - Collection vs Collection<K, V>
 * - Generator vs Generator<TKey, TValue, TSend, TReturn>
 */
final class GenericClassRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        // This rule handles when the doc type has generics but native doesn't
        return str_contains($docType, '<') && !str_contains($nativeType, '<');
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // Extract base class from doc type (before the <)
        $docBase = strstr($docType, '<', before_needle: true);

        if ($docBase === false) {
            return false;
        }

        // Compare base class names (case-insensitive)
        return strtolower($nativeType) === strtolower($docBase);
    }
}
