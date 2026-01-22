<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles key-of<T> type compatibility.
 *
 * key-of<T> represents the keys of an array or object type.
 *
 * Supports:
 * - string vs key-of<T> (when keys are strings)
 * - int vs key-of<T> (when keys are integers)
 * - string|int vs key-of<T> (general case)
 */
final class KeyOfRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        // After stripGenerics, key-of<T> becomes key-of
        return $docType === 'key-of';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // key-of<T> can be string, int, or string|int depending on the array
        // We accept string, int, or array-key (string|int) as compatible
        return in_array($nativeType, ['string', 'int', 'array-key'], true)
            || $nativeType === 'int|string'
            || $nativeType === 'string|int';
    }
}
