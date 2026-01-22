<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Cache;

/**
 * Determines how file changes are detected for cache invalidation.
 */
enum CacheMode: string
{
    /**
     * Use SHA-256 content hash for invalidation.
     * More reliable but slightly slower.
     */
    case Hash = 'hash';

    /**
     * Use file modification time for invalidation.
     * Faster but can miss changes (e.g., after git operations).
     */
    case Mtime = 'mtime';

    /**
     * Disable caching entirely.
     */
    case None = 'none';

    public function isEnabled(): bool
    {
        return $this !== self::None;
    }

    /**
     * Get the cache key for a file based on the cache mode.
     */
    public function getFileKey(string $filePath): ?string
    {
        return match ($this) {
            self::Hash => @hash_file('sha256', $filePath) ?: null,
            self::Mtime => ($mtime = @filemtime($filePath)) !== false ? (string) $mtime : null,
            self::None => null,
        };
    }
}
