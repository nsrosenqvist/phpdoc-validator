<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator\Rules;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;

/**
 * Handles string literal type compatibility.
 *
 * Supports:
 * - string vs "literal" or 'literal'
 */
final class StringLiteralRule implements CompatibilityRuleInterface
{
    public function __construct(
        private readonly TypeClassifier $classifier,
    ) {}

    public function supports(string $nativeType, string $docType): bool
    {
        return $nativeType === 'string';
    }

    public function isCompatible(string $nativeType, string $docType): bool
    {
        return $this->classifier->isStringLiteral($docType);
    }
}
