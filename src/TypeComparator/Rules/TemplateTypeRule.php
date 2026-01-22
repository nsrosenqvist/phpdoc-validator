<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;

/**
 * Handles template/generic type parameter compatibility.
 *
 * Supports:
 * - mixed vs T, TValue, TKey, etc.
 */
final class TemplateTypeRule implements CompatibilityRuleInterface
{
    public function __construct(
        private readonly TypeClassifier $classifier,
    ) {}

    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'mixed';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // Template types (T, TValue, etc.) are compatible with mixed
        return $this->classifier->isTemplateType($docType);
    }
}
