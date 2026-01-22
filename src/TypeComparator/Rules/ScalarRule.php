<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;

/**
 * Handles scalar type compatibility.
 *
 * scalar represents any scalar value (int|float|string|bool).
 *
 * Supports:
 * - int|float|string|bool vs scalar
 * - Any subset union vs scalar
 */
final class ScalarRule implements CompatibilityRuleInterface
{
    public function supports(string $nativeType, string $docType): bool
    {
        return $docType === 'scalar';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        // scalar is equivalent to int|float|string|bool
        // We accept if native type is scalar or any combination of scalar types
        $scalarTypes = ['int', 'float', 'string', 'bool'];

        // Check if native type is a single scalar type
        if (in_array($nativeType, $scalarTypes, true)) {
            return true;
        }

        // Check if native type is a union of scalar types
        $parts = explode('|', $nativeType);
        foreach ($parts as $part) {
            if (!in_array(trim($part), $scalarTypes, true)) {
                return false;
            }
        }

        return count($parts) > 0;
    }
}
