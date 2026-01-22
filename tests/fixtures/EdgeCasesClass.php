<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * A class with various edge cases.
 */
class EdgeCasesClass
{
    /**
     * Method with int vs integer equivalence (should pass).
     *
     * @param integer $count Using long form
     */
    public function integerEquivalence(int $count): void
    {
        // Implementation
    }

    /**
     * Method with bool vs boolean equivalence (should pass).
     *
     * @param boolean $flag Using long form
     */
    public function booleanEquivalence(bool $flag): void
    {
        // Implementation
    }

    /**
     * Method with positive-int (should be compatible with int).
     *
     * @param positive-int $count Positive integer
     */
    public function positiveIntMethod(int $count): void
    {
        // Implementation
    }

    /**
     * Method with non-empty-array (should be compatible with array).
     *
     * @param non-empty-array<string> $items Non-empty array
     */
    public function nonEmptyArrayMethod(array $items): void
    {
        // Implementation
    }

    /**
     * Method with Closure vs callable.
     *
     * @param \Closure $callback The callback
     */
    public function closureMethod(callable $callback): void
    {
        // Implementation
    }

    /**
     * Static method.
     *
     * @param string $name The name
     */
    public static function staticMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Protected method.
     *
     * @param string $name The name
     * @param int $extra Extra param that doesn't exist
     */
    protected function protectedMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Private method.
     *
     * @param string $name The name
     */
    private function privateMethod(string $name): void
    {
        // Implementation
    }
}
