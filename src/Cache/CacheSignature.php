<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Cache;

/**
 * Represents the configuration signature that affects validation results.
 *
 * If any part of the signature changes, the entire cache is invalidated.
 */
final readonly class CacheSignature
{
    private const CACHE_VERSION = 1;

    public function __construct(
        public string $validatorVersion,
        public string $phpVersion,
        public bool $reportMissing,
        public CacheMode $cacheMode,
    ) {}

    /**
     * Check if this signature matches another signature.
     */
    public function matches(self $other): bool
    {
        return $this->validatorVersion === $other->validatorVersion
            && $this->phpVersion === $other->phpVersion
            && $this->reportMissing === $other->reportMissing
            && $this->cacheMode === $other->cacheMode;
    }

    /**
     * Convert the signature to an array for serialization.
     *
     * @return array{cacheVersion: int, validatorVersion: string, phpVersion: string, reportMissing: bool, cacheMode: string}
     */
    public function toArray(): array
    {
        return [
            'cacheVersion' => self::CACHE_VERSION,
            'validatorVersion' => $this->validatorVersion,
            'phpVersion' => $this->phpVersion,
            'reportMissing' => $this->reportMissing,
            'cacheMode' => $this->cacheMode->value,
        ];
    }

    /**
     * Create a signature from a serialized array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        // Check cache version compatibility
        if (!isset($data['cacheVersion']) || $data['cacheVersion'] !== self::CACHE_VERSION) {
            return null;
        }

        if (
            !isset($data['validatorVersion'], $data['phpVersion'], $data['cacheMode'])
            || !isset($data['reportMissing'])
        ) {
            return null;
        }

        $validatorVersion = $data['validatorVersion'];
        $phpVersion = $data['phpVersion'];
        $cacheModeValue = $data['cacheMode'];

        if (!is_string($validatorVersion) || !is_string($phpVersion) || !is_string($cacheModeValue)) {
            return null;
        }

        $cacheMode = CacheMode::tryFrom($cacheModeValue);
        if ($cacheMode === null) {
            return null;
        }

        return new self(
            validatorVersion: $validatorVersion,
            phpVersion: $phpVersion,
            reportMissing: (bool) $data['reportMissing'],
            cacheMode: $cacheMode,
        );
    }
}
