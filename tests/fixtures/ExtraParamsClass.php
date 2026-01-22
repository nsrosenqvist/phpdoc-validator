<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * A class with extra @param tags that don't exist in signatures.
 */
class ExtraParamsClass
{
    /**
     * Method with an extra documented parameter.
     *
     * @param string $name The name
     * @param int $nonexistent This parameter doesn't exist
     */
    public function extraParamMethod(string $name): void
    {
        // Implementation
    }

    /**
     * Method with multiple extra parameters.
     *
     * @param string $name The name
     * @param int $age This doesn't exist
     * @param bool $active This also doesn't exist
     */
    public function multipleExtraParams(string $name): void
    {
        // Implementation
    }

    /**
     * Method with typo in parameter name.
     *
     * @param string $naem Typo in parameter name
     */
    public function typoParamMethod(string $name): void
    {
        // Implementation
    }
}
