<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * Trait with PHPDoc.
 */
trait SampleTrait
{
    /**
     * Trait method with valid doc.
     *
     * @param string $name The name
     */
    public function traitMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Trait method with type mismatch.
     *
     * @param int $name Should be string
     */
    public function traitMethodWithMismatch(string $name): void
    {
        // Implementation
    }
}
