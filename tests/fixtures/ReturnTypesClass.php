<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * Class with various return type scenarios.
 */
class ReturnTypesClass
{
    /**
     * Valid return type documentation.
     *
     * @return string The greeting
     */
    public function validReturn(): string
    {
        return 'hello';
    }

    /**
     * Return type mismatch.
     *
     * @return int Should be string
     */
    public function mismatchedReturn(): string
    {
        return 'hello';
    }

    /**
     * Narrowed return type (valid - doc is more specific).
     *
     * @return string[]
     */
    public function narrowedReturn(): array
    {
        return ['hello'];
    }

    /**
     * Valid nullable return.
     *
     * @return string|null
     */
    public function nullableReturn(): ?string
    {
        return null;
    }

    /**
     * Valid void return.
     *
     * @return void
     */
    public function voidReturn(): void
    {
        // nothing
    }

    /**
     * Constructor - should be skipped.
     *
     * @param string $name Name
     */
    public function __construct(string $name)
    {
        // constructors don't need @return
    }

    /**
     * No @return tag but has return type (for --missing flag).
     *
     * @param string $name Name
     */
    public function missingReturnDoc(string $name): string
    {
        return $name;
    }

    /**
     * Method without return type - no validation needed.
     */
    public function noReturnType()
    {
        return 'anything';
    }

    /**
     * Generic return type.
     *
     * @return array<string, int>
     */
    public function genericReturn(): array
    {
        return ['count' => 1];
    }
}
