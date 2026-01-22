<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

use NsRosenqvist\PhpDocValidator\TypeComparator\CompatibilityRuleInterface;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ArrayKeyRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ArrayTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\CallableTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ConditionalTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\GenericClassRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\IntTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\IterableTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\KeyOfRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\NeverTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\NumericRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ObjectTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ResourceTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ScalarRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\StringLiteralRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\StringTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\TemplateTypeRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\Rules\ValueOfRule;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeClassifier;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeNormalizer;
use NsRosenqvist\PhpDocValidator\TypeComparator\TypeParser;

/**
 * Compares types between method signatures and PHPDoc annotations.
 *
 * Uses a rule-based architecture for extensibility.
 */
final class TypeComparator
{
    private readonly TypeParser $parser;
    private readonly TypeNormalizer $normalizer;
    private readonly TypeClassifier $classifier;

    /**
     * @var list<CompatibilityRuleInterface>
     */
    private readonly array $rules;

    /**
     * @param list<CompatibilityRuleInterface>|null $rules Custom rules (uses defaults if null)
     */
    public function __construct(?array $rules = null)
    {
        $this->parser = new TypeParser();
        $this->classifier = new TypeClassifier();
        $this->normalizer = new TypeNormalizer($this->parser);

        $this->rules = $rules ?? $this->createDefaultRules();
    }

    /**
     * Check if a documented type is compatible with the actual signature type.
     *
     * @param string $actualType The type from the method signature
     * @param string $docType The type from the PHPDoc @param tag
     */
    public function areCompatible(string $actualType, string $docType): bool
    {
        $normalizedActual = $this->normalize($actualType);
        $normalizedDoc = $this->normalize($docType);

        // If either normalized to null, they can't be compared
        if ($normalizedActual === null || $normalizedDoc === null) {
            return false;
        }

        // Exact match after normalization
        if ($normalizedActual === $normalizedDoc) {
            return true;
        }

        // Compare as sets for union types
        $actualParts = $this->parser->parseUnionType($normalizedActual);
        $docParts = $this->parser->parseUnionType($docType); // Use original for special type detection

        // If both are union types with same parts (order-independent)
        if ($this->parser->setsEqual($actualParts, $this->parser->parseUnionType($normalizedDoc))) {
            return true;
        }

        // Check if union types are compatible part-by-part
        if ($this->areUnionTypesCompatible($actualParts, $docParts)) {
            return true;
        }

        // Base type comparison (strip generics)
        $actualBase = $this->parser->stripGenerics($normalizedActual);
        $docBase = $this->parser->stripGenerics($normalizedDoc);

        if ($actualBase === $docBase) {
            return true;
        }

        // Try compatibility rules
        if ($this->checkRules($actualBase, $docBase)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a type string for comparison.
     */
    public function normalize(?string $type): ?string
    {
        return $this->normalizer->normalize($type);
    }

    /**
     * Check if union types are compatible by comparing parts.
     *
     * @param list<string> $actualParts Parts from normalized actual type (lowercase)
     * @param list<string> $docParts Parts from original doc type (case preserved)
     */
    private function areUnionTypesCompatible(array $actualParts, array $docParts): bool
    {
        // For each actual part, find compatible doc parts
        $matchedDocParts = [];

        foreach ($actualParts as $actualPart) {
            $foundMatch = false;

            foreach ($docParts as $index => $docPart) {
                if (in_array($index, $matchedDocParts, true)) {
                    continue;
                }

                // Check for string literal unions: string matches "a"|"b"|"c"
                if ($actualPart === 'string' && $this->classifier->isStringLiteral($docPart)) {
                    $matchedDocParts[] = $index;
                    $foundMatch = true;
                    continue;
                }

                // Normalize both for comparison
                $actualBase = $this->parser->stripGenerics(strtolower($actualPart));
                $docBase = $this->parser->stripGenerics(strtolower($docPart));

                if ($actualBase === $docBase) {
                    $matchedDocParts[] = $index;
                    $foundMatch = true;
                    continue;
                }

                // Try compatibility rules
                if ($this->checkRules($actualBase, $docBase)) {
                    $matchedDocParts[] = $index;
                    $foundMatch = true;
                    continue;
                }

                // Check for template types: mixed matches T, TValue, etc.
                if ($actualPart === 'mixed' && $this->classifier->isTemplateType($docPart)) {
                    $matchedDocParts[] = $index;
                    $foundMatch = true;
                    continue;
                }
            }

            if (!$foundMatch) {
                return false;
            }
        }

        // Check that all doc parts were matched (or are string literals that matched 'string')
        $unmatchedDocParts = array_diff(array_keys($docParts), $matchedDocParts);
        foreach ($unmatchedDocParts as $index) {
            $docPart = $docParts[$index];
            // Unmatched string literals are acceptable if 'string' was in actual
            if ($this->classifier->isStringLiteral($docPart) && in_array('string', $actualParts, true)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Check if any rule considers the types compatible.
     */
    private function checkRules(string $nativeType, string $docType): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->supports($nativeType, $docType) && $rule->isCompatible($nativeType, $docType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create the default set of compatibility rules.
     *
     * @return list<CompatibilityRuleInterface>
     */
    private function createDefaultRules(): array
    {
        return [
            new ArrayTypeRule(),
            new StringTypeRule(),
            new StringLiteralRule($this->classifier),
            new IntTypeRule($this->classifier),
            new CallableTypeRule(),
            new ObjectTypeRule($this->classifier),
            new IterableTypeRule(),
            new TemplateTypeRule($this->classifier),
            new KeyOfRule(),
            new ValueOfRule(),
            new ConditionalTypeRule(),
            new GenericClassRule(),
            new ArrayKeyRule(),
            new ScalarRule(),
            new NumericRule(),
            new NeverTypeRule(),
            new ResourceTypeRule(),
        ];
    }
}
