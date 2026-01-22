<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator;

/**
 * Parses and manipulates type strings.
 */
final class TypeParser
{
    /**
     * Parse a union type into its constituent parts.
     *
     * Handles nested types like generics, callables, and array shapes.
     *
     * @return list<string>
     */
    public function parseUnionType(string $type): array
    {
        // Simple split for non-nested unions (no generics, callables, or array shapes)
        if (!str_contains($type, '<') && !str_contains($type, '(') && !str_contains($type, '{')) {
            $parts = explode('|', $type);
            $parts = array_map('trim', $parts);
            sort($parts);

            return $parts;
        }

        // Handle nested types (generics, callables, array shapes) by splitting carefully
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
     * Strip generic type parameters and array shapes for base type comparison.
     */
    public function stripGenerics(string $type): string
    {
        // Remove array shapes (everything between { and }, handling nesting)
        $type = $this->removeBalancedBrackets($type, '{', '}');

        // Remove generics (everything between < and >, handling nesting)
        $type = $this->removeBalancedBrackets($type, '<', '>');

        return $type;
    }

    /**
     * Extract the generic type parameter from a type like array<T> or list<T>.
     */
    public function extractGenericParameter(string $type): ?string
    {
        if (!str_contains($type, '<')) {
            return null;
        }

        $start = strpos($type, '<');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $param = '';

        for ($i = $start + 1; $i < strlen($type); $i++) {
            $char = $type[$i];

            if ($char === '<') {
                $depth++;
                $param .= $char;
            } elseif ($char === '>') {
                if ($depth === 0) {
                    return trim($param);
                }
                $depth--;
                $param .= $char;
            } else {
                $param .= $char;
            }
        }

        return null;
    }

    /**
     * Remove balanced bracket pairs from a string, handling nesting.
     */
    public function removeBalancedBrackets(string $type, string $open, string $close): string
    {
        $result = '';
        $depth = 0;

        for ($i = 0; $i < strlen($type); $i++) {
            $char = $type[$i];

            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;
            } elseif ($depth === 0) {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Check if two arrays contain the same elements (order-independent).
     *
     * @param list<string> $a
     * @param list<string> $b
     */
    public function setsEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }

        sort($a);
        sort($b);

        return $a === $b;
    }
}
