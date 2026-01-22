<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * A class with type mismatches between docs and signatures.
 */
class TypeMismatchClass
{
    /**
     * Method with wrong type documented.
     *
     * @param int $name Should be string
     */
    public function wrongTypeMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Method with array vs string mismatch.
     *
     * @param array $name Should be string
     */
    public function arrayVsStringMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Method with nullable mismatch.
     *
     * @param string $name Should be nullable
     */
    public function nullableMismatchMethod(?string $name): void
    {
        // Implementation - this is actually valid (doc is more restrictive)
    }

    /**
     * Method with object vs scalar mismatch.
     *
     * @param \DateTime $date Should be string
     */
    public function objectMismatchMethod(string $date): void
    {
        // Implementation
    }

    /**
     * Method with incompatible union type.
     *
     * @param bool|float $value Should be string|int
     */
    public function unionMismatchMethod(string|int $value): void
    {
        // Implementation
    }
}
