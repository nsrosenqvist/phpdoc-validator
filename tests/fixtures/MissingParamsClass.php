<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * Class to test missing param detection.
 */
class MissingParamsClass
{
    /**
     * Method with some params documented but not all.
     *
     * @param string $name The name
     */
    public function partiallyDocumented(string $name, int $age, bool $active): void
    {
        // Implementation
    }

    /**
     * Method with doc block but no @param tags.
     *
     * This method has documentation but no parameter docs.
     *
     * @return void
     */
    public function noParamTags(string $name, int $age): void
    {
        // Implementation
    }

    // Method with no doc block at all
    public function noDocBlock(string $name, int $age): void
    {
        // Implementation
    }

    /**
     * Method with all params documented (should pass --missing check).
     *
     * @param string $name The name
     * @param int $age The age
     */
    public function fullyDocumented(string $name, int $age): void
    {
        // Implementation
    }
}
