<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * Class with complex generic types.
 */
class ComplexTypesClass
{
    /**
     * Method with nested generics.
     *
     * @param array<string, array<int, bool>> $data Nested array
     */
    public function nestedGenerics(array $data): void
    {
        // Implementation
    }

    /**
     * Method with iterable generic.
     *
     * @param iterable<string> $items Iterable of strings
     */
    public function iterableGeneric(iterable $items): void
    {
        // Implementation
    }

    /**
     * Method with callable signature.
     *
     * @param callable(string, int): bool $callback Typed callable
     */
    public function callableSignature(callable $callback): void
    {
        // Implementation
    }

    /**
     * Method with intersection type.
     *
     * @param \Countable&\Iterator $value Intersection type
     */
    public function intersectionType(\Countable&\Iterator $value): void
    {
        // Implementation
    }

    /**
     * Method with default value.
     *
     * @param string $name The name
     * @param int $count The count
     */
    public function defaultValues(string $name = 'default', int $count = 0): void
    {
        // Implementation
    }

    /**
     * Method with variadic parameter.
     *
     * @param string ...$names Multiple names
     */
    public function variadicMethod(string ...$names): void
    {
        // Implementation
    }

    /**
     * Method with reference parameter.
     *
     * @param string $name Pass by reference
     */
    public function referenceMethod(string &$name): void
    {
        // Implementation
    }
}
