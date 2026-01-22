<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles resource type compatibility.
 *
 * Supports:
 * - resource vs closed-resource
 * - resource vs open-resource
 */
final class ResourceTypeRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'resource';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        return in_array($docType, ['closed-resource', 'open-resource'], true);
    }
}
