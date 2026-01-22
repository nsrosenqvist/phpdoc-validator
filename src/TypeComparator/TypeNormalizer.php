<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\TypeComparator;

/**
 * Normalizes type strings for comparison.
 */
final class TypeNormalizer
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

    public function __construct(
        private readonly TypeParser $parser,
    ) {}

    /**
     * Normalize a type string for comparison.
     */
    public function normalize(?string $type): ?string
    {
        if ($type === null || trim($type) === '') {
            return null;
        }

        $type = trim($type);

        // Remove leading backslashes from fully qualified class names
        $type = ltrim($type, '\\');

        // Remove backslashes before class names
        $type = preg_replace('/\\\\([A-Za-z])/', '$1', $type) ?? $type;

        // Convert nullable syntax to union
        if (str_starts_with($type, '?')) {
            $type = substr($type, 1) . '|null';
        }

        // Normalize types within the string (lowercase native types, apply aliases)
        $type = $this->applyAliases($type);

        // Sort union types for consistent comparison
        if (str_contains($type, '|') && !str_contains($type, '(')) {
            $parts = $this->parser->parseUnionType($type);
            $parts = array_map('strtolower', $parts);
            sort($parts);
            $type = implode('|', $parts);
        }

        // Sort intersection types for consistent comparison
        if (str_contains($type, '&') && !str_contains($type, '(')) {
            $parts = explode('&', $type);
            $parts = array_map(fn($p) => strtolower(trim($p)), $parts);
            sort($parts);
            $type = implode('&', $parts);
        }

        return strtolower($type);
    }

    /**
     * Apply type aliases to a type string.
     */
    private function applyAliases(string $type): string
    {
        foreach (self::TYPE_ALIASES as $alias => $canonical) {
            $type = preg_replace('/\b' . preg_quote($alias, '/') . '\b/i', $canonical, $type) ?? $type;
        }

        return $type;
    }
}
