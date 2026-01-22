<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;

/**
 * Handles object type compatibility.
 *
 * Supports:
 * - object vs any class name (doc is more specific)
 * - object vs object{prop: type} (object shapes)
 */
final class ObjectTypeRule implements CompatibilityRuleInterface
{
    public function __construct(
        private readonly TypeClassifier $classifier,
    ) {}

    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'object';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // object{prop: type} object shape syntax
        if (str_starts_with($docType, 'object{') || str_starts_with($docType, 'object<')) {
            return true;
        }

        // object signature accepts any class name in doc (doc is more specific)
        return $this->classifier->isClassName($docType);
    }
}
