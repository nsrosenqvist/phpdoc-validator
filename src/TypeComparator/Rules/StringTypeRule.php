<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles string type compatibility.
 *
 * Supports:
 * - string vs class-string, class-string<T>
 * - string vs non-empty-string
 * - string vs numeric-string
 * - string vs callable-string
 * - string vs literal-string
 * - string vs lowercase-string
 * - string vs truthy-string
 * - string vs trait-string
 * - string vs interface-string
 */
final class StringTypeRule implements CompatibilityRuleInterface
{
    /**
     * PHPDoc string subtypes that are compatible with native string.
     *
     * @var list<string>
     */
    private const STRING_SUBTYPES = [
        'class-string',
        'non-empty-string',
        'numeric-string',
        'callable-string',
        'literal-string',
        'lowercase-string',
        'truthy-string',
        'non-falsy-string',
        'trait-string',
        'interface-string',
    ];

    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'string';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // Check for exact matches or prefixes of known subtypes
        foreach (self::STRING_SUBTYPES as $subtype) {
            if (str_starts_with($docType, $subtype)) {
                return true;
            }
        }

        // General pattern: anything-string is compatible with string
        if (str_contains($docType, '-string')) {
            return true;
        }

        return false;
    }
}
