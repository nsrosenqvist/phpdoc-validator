<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles never type compatibility.
 *
 * Supports:
 * - never vs never-return
 * - never vs no-return
 * - never vs never-returns
 */
final class NeverTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'never';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // These are all aliases for never
        return in_array($docType, ['never-return', 'never-returns', 'no-return', 'noreturn'], true);
    }
}
