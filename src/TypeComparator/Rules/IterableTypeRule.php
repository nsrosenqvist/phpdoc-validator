<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles iterable type compatibility.
 *
 * Supports:
 * - iterable vs iterable<T>
 * - iterable vs iterable<K, V>
 */
final class IterableTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'iterable';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // iterable<T> or iterable<K, V> is compatible with iterable
        return str_starts_with($docType, 'iterable');
    }
}
