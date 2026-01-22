<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles value-of<T> type compatibility.
 *
 * value-of<T> represents the values of an array or the cases of a backed enum.
 *
 * Supports:
 * - mixed vs value-of<T> (general case, value type is unknown)
 * - string vs value-of<T> (when values are strings)
 * - int vs value-of<T> (when values are integers)
 */
final class ValueOfRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        // After stripGenerics, value-of<T> becomes value-of
        return $docType === 'value-of';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // value-of<T> can be any type depending on the array/enum values
        // We accept mixed, string, int as compatible since those are common value types
        return in_array($nativeType, ['mixed', 'string', 'int', 'float', 'bool', 'array'], true);
    }
}
