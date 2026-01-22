<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles numeric type compatibility.
 *
 * numeric represents numeric values (int|float).
 *
 * Supports:
 * - int vs numeric
 * - float vs numeric
 * - int|float vs numeric
 */
final class NumericRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $docType === 'numeric';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // numeric is equivalent to int|float
        $numericTypes = ['int', 'float'];

        // Check if native type is a single numeric type
        if (in_array($nativeType, $numericTypes, true)) {
            return true;
        }

        // Check if native type is int|float or float|int
        return in_array($nativeType, ['int|float', 'float|int'], true);
    }
}
