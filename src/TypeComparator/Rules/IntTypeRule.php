<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;

/**
 * Handles int type compatibility.
 *
 * Supports:
 * - int vs positive-int, negative-int
 * - int vs non-negative-int, non-positive-int
 * - int vs int<min, max> (int ranges)
 * - int vs int-mask<T>, int-mask-of<T>
 * - int vs integer literal types (0, 1, 42)
 */
final class IntTypeRule implements CompatibilityRuleInterface
{
    /**
     * PHPDoc int subtypes that are compatible with native int.
     *
     * @var list<string>
     */
    private const INT_SUBTYPES = [
        'positive-int',
        'negative-int',
        'non-negative-int',
        'non-positive-int',
        'int-mask',
        'int-mask-of',
    ];

    public function __construct(
        private readonly TypeClassifier $classifier,
    ) {}

    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'int';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // Check for exact matches or prefixes of known subtypes
        foreach (self::INT_SUBTYPES as $subtype) {
            if (str_starts_with($docType, $subtype)) {
                return true;
            }
        }

        // int<min, max> range syntax
        if (str_starts_with($docType, 'int<')) {
            return true;
        }

        // General pattern: anything-int is compatible with int
        if (str_contains($docType, '-int')) {
            return true;
        }

        // Integer literals (0, 1, 42, -1)
        if ($this->classifier->isIntLiteral($docType)) {
            return true;
        }

        return false;
    }
}
