<?php

// This file has a syntax error to test graceful handling

declare(strict_types=1);

namespace TestFixtures;

class BrokenClass
{
    public function brokenMethod(string $name
    {
        // Missing closing parenthesis
    }
}
