<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles callable type compatibility.
 *
 * Supports:
 * - callable vs Closure
 * - Closure vs callable
 * - callable vs callable(T): R (callable signatures)
 */
final class CallableTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'callable' || $nativeType === 'closure';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // callable and Closure are interchangeable
        if ($nativeType === 'callable' && $docType === 'closure') {
            return true;
        }

        if ($nativeType === 'closure' && $docType === 'callable') {
            return true;
        }

        // callable vs callable(...): T (callable with signature)
        if ($nativeType === 'callable' && str_starts_with($docType, 'callable(')) {
            return true;
        }

        // Closure vs Closure(...): T
        if ($nativeType === 'closure' && str_starts_with($docType, 'closure(')) {
            return true;
        }

        return false;
    }
}
