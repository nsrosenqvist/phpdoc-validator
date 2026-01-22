<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles conditional type compatibility.
 *
 * Conditional types have the form: (T is U ? V : W)
 *
 * Supports:
 * - mixed vs conditional types (since the result can vary)
 * - Specific types when the conditional resolves to that type
 */
final class ConditionalTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        // Conditional types contain " is " and " ? "
        return str_contains($docType, ' is ') && str_contains($docType, ' ? ');
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // For conditional types, we accept mixed since the actual type
        // depends on runtime conditions we can't evaluate statically
        // This is a permissive approach - the doc is providing more context
        // than the native type can express
        return $nativeType === 'mixed';
    }
}
