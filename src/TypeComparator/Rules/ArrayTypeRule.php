<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles array type compatibility.
 *
 * Supports:
 * - array vs array<K, V>
 * - array vs Type[]
 * - array vs list<T>, non-empty-list<T>
 * - array vs non-empty-array<T>
 * - array vs array{key: type} (array shapes)
 * - array vs callable-array
 */
final class ArrayTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'array';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // Type[] syntax - matches anything ending with []
        if (str_ends_with($docType, '[]')) {
            return true;
        }

        // array<K,V> syntax
        if (str_starts_with($docType, 'array')) {
            return true;
        }

        // list<T>, non-empty-list<T>
        if (str_starts_with($docType, 'list') || str_starts_with($docType, 'non-empty-list')) {
            return true;
        }

        // non-empty-array<T>
        if (str_starts_with($docType, 'non-empty-array')) {
            return true;
        }

        // callable-array (array callable like [$obj, 'method'])
        if ($docType === 'callable-array') {
            return true;
        }

        return false;
    }
}
