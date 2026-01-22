<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Compares types between method signatures and PHPDoc annotations.
 */
final class TypeComparator
{
    /**
     * Type aliases that should be considered equivalent.
     *
     * @var array<string, string>
     */
    private const TYPE_ALIASES = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
        'real' => 'float',
        'callback' => 'callable',
    ];

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
        $actualParts = $this->parseUnionType($normalizedActual);
        $docParts = $this->parseUnionType($normalizedDoc);

        // If both are union types with same parts (order-independent)
        if ($this->setsEqual($actualParts, $docParts)) {
            return true;
        }

        // Base type comparison (strip generics)
        $actualBase = $this->stripGenerics($normalizedActual);
        $docBase = $this->stripGenerics($normalizedDoc);

        if ($actualBase === $docBase) {
            return true;
        }

        // Handle PHPDoc-specific types that are compatible with PHP native types
        if ($this->isDocTypeCompatibleWithNative($actualBase, $docBase)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a type string for comparison.
     */
    public function normalize(?string $type): ?string
    {
        if ($type === null || trim($type) === '') {
            return null;
        }

        $type = trim($type);
        $type = strtolower($type);

        // Remove leading backslashes from fully qualified class names
        $type = ltrim($type, '\\');

        // Remove backslashes before class names in intersection types
        $type = preg_replace('/\\\\([a-z])/', '$1', $type) ?? $type;

        // Convert nullable syntax to union
        if (str_starts_with($type, '?')) {
            $type = substr($type, 1) . '|null';
        }

        // Apply type aliases
        foreach (self::TYPE_ALIASES as $alias => $canonical) {
            $type = preg_replace('/\b' . preg_quote($alias, '/') . '\b/', $canonical, $type) ?? $type;
        }

        // Sort union types for consistent comparison
        if (str_contains($type, '|') && !str_contains($type, '(')) {
            $parts = explode('|', $type);
            $parts = array_map('trim', $parts);
            sort($parts);
            $type = implode('|', $parts);
        }

        // Sort intersection types for consistent comparison
        if (str_contains($type, '&') && !str_contains($type, '(')) {
            $parts = explode('&', $type);
            $parts = array_map('trim', $parts);
            sort($parts);
            $type = implode('&', $parts);
        }

        return $type;
    }

    /**
     * Parse a union type into its constituent parts.
     *
     * @return list<string>
     */
    private function parseUnionType(string $type): array
    {
        // Simple split for non-nested unions
        if (!str_contains($type, '<') && !str_contains($type, '(')) {
            $parts = explode('|', $type);
            $parts = array_map('trim', $parts);
            sort($parts);

            return $parts;
        }

        // Handle nested types (generics, callables) by splitting carefully
        $parts = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($type); $i++) {
            $char = $type[$i];

            if ($char === '<' || $char === '(' || $char === '[' || $char === '{') {
                $depth++;
                $current .= $char;
            } elseif ($char === '>' || $char === ')' || $char === ']' || $char === '}') {
                $depth--;
                $current .= $char;
            } elseif ($char === '|' && $depth === 0) {
                if (trim($current) !== '') {
                    $parts[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        sort($parts);

        return $parts;
    }

    /**
     * Strip generic type parameters for base type comparison.
     */
    private function stripGenerics(string $type): string
    {
        // Remove everything between < and >
        return (string) preg_replace('/<[^>]*>/', '', $type);
    }

    /**
     * Check if two arrays contain the same elements (order-independent).
     *
     * @param list<string> $a
     * @param list<string> $b
     */
    private function setsEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }

        sort($a);
        sort($b);

        return $a === $b;
    }

    /**
     * Check if a PHPDoc-specific type is compatible with a native PHP type.
     */
    private function isDocTypeCompatibleWithNative(string $actualBase, string $docBase): bool
    {
        // class-string<T> is compatible with string
        if ($actualBase === 'string' && str_starts_with($docBase, 'class-string')) {
            return true;
        }

        // Array compatibility: PHPDoc can specify more specific array types
        // - Type[] syntax (e.g., string[], \Tag[])
        // - array<K,V> syntax
        // - list<T>, non-empty-list<T>, non-empty-array<T>
        if ($actualBase === 'array') {
            // Type[] syntax - matches anything ending with []
            if (str_ends_with($docBase, '[]')) {
                return true;
            }

            if (str_starts_with($docBase, 'list')
                || str_starts_with($docBase, 'non-empty-list')
                || str_starts_with($docBase, 'non-empty-array')
                || str_starts_with($docBase, 'array')) {
                return true;
            }
        }

        // iterable<T> is compatible with iterable
        if ($actualBase === 'iterable' && str_starts_with($docBase, 'iterable')) {
            return true;
        }

        // positive-int, negative-int, non-negative-int, non-positive-int are compatible with int
        if ($actualBase === 'int') {
            if (str_contains($docBase, '-int') || $docBase === 'positive-int'
                || $docBase === 'negative-int' || $docBase === 'non-negative-int'
                || $docBase === 'non-positive-int') {
                return true;
            }
        }

        // numeric-string is compatible with string
        if ($actualBase === 'string' && str_contains($docBase, '-string')) {
            return true;
        }

        // callable-string is compatible with string
        if ($actualBase === 'string' && $docBase === 'callable-string') {
            return true;
        }

        // \Closure is compatible with callable
        if ($actualBase === 'callable' && $docBase === 'closure') {
            return true;
        }
        if ($actualBase === 'closure' && $docBase === 'callable') {
            return true;
        }

        return false;
    }
}
