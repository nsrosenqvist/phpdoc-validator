<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator;

/**
 * Provides helper methods for identifying type patterns.
 */
final class TypeClassifier
{
    /**
     * Native PHP types.
     *
     * @var list<string>
     */
    private const NATIVE_TYPES = [
        'string',
        'int',
        'float',
        'bool',
        'array',
        'object',
        'callable',
        'iterable',
        'null',
        'void',
        'never',
        'mixed',
        'true',
        'false',
        'self',
        'static',
        'parent',
        'resource',
    ];

    /**
     * Check if a type looks like a class name (not a PHP native type).
     */
    public function isClassName(string $type): bool
    {
        return !in_array($type, self::NATIVE_TYPES, true);
    }

    /**
     * Check if a type looks like a template/generic type parameter (T, TValue, etc.).
     */
    public function isTemplateType(string $type): bool
    {
        // Template types are typically single uppercase letters or
        // uppercase letter followed by more letters (TValue, TKey, etc.)
        // Strip leading backslash if present (type resolver may add it treating T as class)
        $type = ltrim($type, '\\');

        // Should not contain any other backslashes (which would indicate a namespaced class)
        if (str_contains($type, '\\')) {
            return false;
        }

        // Match patterns like: T, TValue, TKey, T1, etc.
        return (bool) preg_match('/^[A-Z][a-zA-Z0-9]*$/', $type);
    }

    /**
     * Check if a type is a string literal (e.g., "low", 'high').
     */
    public function isStringLiteral(string $type): bool
    {
        // String literals are quoted with single or double quotes
        return (bool) preg_match('/^["\'][^"\']*["\']$/', $type);
    }

    /**
     * Check if a type is an integer literal (e.g., 0, 1, 42).
     */
    public function isIntLiteral(string $type): bool
    {
        return (bool) preg_match('/^-?\d+$/', $type);
    }

    /**
     * Check if a type is a native PHP type.
     */
    public function isNativeType(string $type): bool
    {
        return in_array($type, self::NATIVE_TYPES, true);
    }
}
