<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * A class with all valid PHPDoc annotations.
 */
class ValidClass
{
    /**
     * Method with correctly documented parameters.
     *
     * @param string $name The user's name
     * @param int $age The user's age
     * @param bool $active Whether the user is active
     */
    public function validMethod(string $name, int $age, bool $active): void
    {
        // Implementation
    }

    /**
     * Method with nullable types.
     *
     * @param string|null $name Optional name
     * @param int|null $count Optional count
     */
    public function nullableMethod(?string $name, ?int $count): void
    {
        // Implementation
    }

    /**
     * Method with array generics.
     *
     * @param array<string, int> $data The data array
     * @param list<string> $items List of items
     */
    public function genericMethod(array $data, array $items): void
    {
        // Implementation
    }

    /**
     * Method with class-string type.
     *
     * @param class-string<\DateTime> $class The class name
     */
    public function classStringMethod(string $class): void
    {
        // Implementation
    }

    /**
     * Method with union types.
     *
     * @param string|int $value The value
     */
    public function unionMethod(string|int $value): void
    {
        // Implementation
    }

    /**
     * Method without parameters.
     */
    public function noParamsMethod(): void
    {
        // Implementation
    }

    /**
     * A method with no @param tags but with other doc content.
     *
     * @return void
     */
    public function noParamTagsMethod(string $name): void
    {
        // Implementation
    }

    // Method without any doc comment
    public function undocumentedMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Method with promoted constructor property syntax.
     *
     * @param string $name The name
     * @param int $age The age
     */
    public function __construct(
        public string $name,
        protected int $age,
    ) {}
}
