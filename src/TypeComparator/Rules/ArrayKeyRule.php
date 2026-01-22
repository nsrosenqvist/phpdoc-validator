<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles array-key type compatibility.
 *
 * array-key represents valid array keys (string|int).
 *
 * Supports:
 * - string|int vs array-key
 * - int|string vs array-key
 */
final class ArrayKeyRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $docType === 'array-key';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // array-key is equivalent to string|int
        return in_array($nativeType, ['int|string', 'string|int'], true);
    }
}
