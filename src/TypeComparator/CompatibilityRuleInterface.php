<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator;

/**
 * Interface for type compatibility rules.
 *
 * Each rule checks if a specific PHPDoc type pattern is compatible
 * with a native PHP type.
 */
interface CompatibilityRuleInterface
{
    /**
     * Check if this rule can handle the given type pair.
     *
     * @param string $nativeType The normalized native PHP type
     * @param string $docType The normalized PHPDoc type
     */
    public function supports(string $nativeType, string $docType): bool;

    /**
     * Check if the doc type is compatible with the native type.
     *
     * This method is only called if supports() returns true.
     *
     * @param string $nativeType The normalized native PHP type
     * @param string $docType The normalized PHPDoc type
     */
    public function isCompatible(string $nativeType, string $docType): bool;
}
