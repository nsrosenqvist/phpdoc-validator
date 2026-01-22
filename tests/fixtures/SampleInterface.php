<?php

declare(strict_types=1);

namespace TestFixtures;

/**
 * Interface with PHPDoc.
 */
interface SampleInterface
{
    /**
     * Interface method with valid doc.
     *
     * @param string $name The name
     */
    public function interfaceMethod(string $name): void;

    /**
     * Interface method with extra param.
     *
     * @param string $name The name
     * @param int $extra This doesn't exist
     */
    public function interfaceMethodWithExtra(string $name): void;
}
