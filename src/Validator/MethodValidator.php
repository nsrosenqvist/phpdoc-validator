<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Validator;

use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\IssueType;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use NsRosenqvist\PhpDocValidator\Parser\DocBlockParser;
use NsRosenqvist\PhpDocValidator\TypeComparator;

/**
 * Validates method PHPDoc annotations against their signatures.
 *
 * Handles validation of both @param and @return tags.
 */
final class MethodValidator
{
    public function __construct(
        private readonly DocBlockParser $docBlockParser,
        private readonly TypeComparator $typeComparator,
    ) {}

    /**
     * Validate a method's PHPDoc against its signature.
     *
     * @return list<Issue>
     */
    public function validate(MethodInfo $method, bool $reportMissing = false): array
    {
        $issues = [];

        $issues = array_merge($issues, $this->validateParams($method, $reportMissing));
        $issues = array_merge($issues, $this->validateReturn($method, $reportMissing));

        return $issues;
    }

    /**
     * Validate @param tags against method parameters.
     *
     * @return list<Issue>
     */
    public function validateParams(MethodInfo $method, bool $reportMissing = false): array
    {
        $issues = [];

        // Skip methods without doc comments (unless reporting missing)
        if (!$method->hasDocComment()) {
            if ($reportMissing && $method->parameters !== []) {
                foreach (array_keys($method->parameters) as $paramName) {
                    $issues[] = $this->createMissingParamIssue($paramName);
                }
            }

            return $issues;
        }

        $docComment = $method->docComment;
        assert($docComment !== null);

        // Validate @param tags if present
        if ($this->docBlockParser->hasParamTags($docComment)) {
            $docParams = $this->docBlockParser->parseParams($docComment);
            $actualParams = $method->parameters;

            // Check for extra documented params that don't exist in signature
            foreach ($docParams as $paramName => $docType) {
                if (!array_key_exists($paramName, $actualParams)) {
                    $issues[] = new Issue(
                        type: IssueType::ExtraParam->value,
                        paramName: $paramName,
                        message: "Extra @param \${$paramName} not in method signature",
                    );
                }
            }

            // Check for type mismatches
            foreach ($docParams as $paramName => $docType) {
                if (!array_key_exists($paramName, $actualParams)) {
                    continue; // Already reported as extra param
                }

                $actualType = $actualParams[$paramName];

                // Both types must be present to compare
                if ($actualType !== null && $docType !== null) {
                    if (!$this->typeComparator->areCompatible($actualType, $docType)) {
                        $issues[] = new Issue(
                            type: IssueType::TypeMismatch->value,
                            paramName: $paramName,
                            message: "Type mismatch for \${$paramName}: signature has '{$actualType}', doc has '{$docType}'",
                            expectedType: $actualType,
                            actualType: $docType,
                        );
                    }
                }
            }

            // Check param order
            $docOrder = $this->docBlockParser->getParamOrder($docComment);
            $actualOrder = array_keys($actualParams);
            // Only compare params that exist in both
            $docOrderFiltered = array_values(array_intersect($docOrder, $actualOrder));
            $actualOrderFiltered = array_values(array_intersect($actualOrder, $docOrder));

            if ($docOrderFiltered !== [] && $docOrderFiltered !== $actualOrderFiltered) {
                $issues[] = new Issue(
                    type: IssueType::ParamOrder->value,
                    paramName: '@params',
                    message: 'Parameter order in @param tags does not match method signature',
                    expectedType: implode(', ', $actualOrderFiltered),
                    actualType: implode(', ', $docOrderFiltered),
                );
            }

            // Check for missing documentation (only if flag is set)
            if ($reportMissing) {
                foreach (array_keys($actualParams) as $paramName) {
                    if (!array_key_exists($paramName, $docParams)) {
                        $issues[] = $this->createMissingParamIssue($paramName);
                    }
                }
            }
        } elseif ($reportMissing && $method->parameters !== []) {
            // No @param tags but method has parameters - report missing if flag set
            foreach (array_keys($method->parameters) as $paramName) {
                $issues[] = $this->createMissingParamIssue($paramName);
            }
        }

        return $issues;
    }

    /**
     * Validate @return tag against method return type.
     *
     * @return list<Issue>
     */
    public function validateReturn(MethodInfo $method, bool $reportMissing = false): array
    {
        $issues = [];

        // Skip constructors and destructors - they don't have meaningful return types
        if (in_array($method->name, ['__construct', '__destruct'], true)) {
            return [];
        }

        $actualReturnType = $method->returnType;
        $docComment = $method->docComment;

        // If no doc comment, check if we should report missing @return
        if ($docComment === null || !$method->hasDocComment()) {
            if ($reportMissing && $actualReturnType !== null && $actualReturnType !== 'void') {
                $issues[] = $this->createMissingReturnIssue($actualReturnType);
            }

            return $issues;
        }

        $hasReturnTag = $this->docBlockParser->hasReturnTag($docComment);
        $docReturnType = $this->docBlockParser->parseReturn($docComment);

        // Check for missing @return when method has a return type
        if ($reportMissing && !$hasReturnTag && $actualReturnType !== null && $actualReturnType !== 'void') {
            $issues[] = $this->createMissingReturnIssue($actualReturnType);

            return $issues;
        }

        // Check for type mismatch if both are present
        if ($actualReturnType !== null && $docReturnType !== null) {
            if (!$this->typeComparator->areCompatible($actualReturnType, $docReturnType)) {
                $issues[] = new Issue(
                    type: IssueType::ReturnMismatch->value,
                    paramName: '@return',
                    message: "Return type mismatch: signature has '{$actualReturnType}', doc has '{$docReturnType}'",
                    expectedType: $actualReturnType,
                    actualType: $docReturnType,
                );
            }
        }

        return $issues;
    }

    private function createMissingParamIssue(string $paramName): Issue
    {
        return new Issue(
            type: IssueType::MissingParam->value,
            paramName: $paramName,
            message: "Missing @param documentation for \${$paramName}",
        );
    }

    private function createMissingReturnIssue(string $returnType): Issue
    {
        return new Issue(
            type: IssueType::MissingReturn->value,
            paramName: '@return',
            message: "Missing @return documentation for return type '{$returnType}'",
        );
    }
}
