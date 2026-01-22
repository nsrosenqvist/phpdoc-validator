<?php

declare(strict_types=1);

/**
 * Standalone function with valid doc.
 *
 * @param string $name The name
 * @param int $age The age
 */
function validFunction(string $name, int $age): void
{
    // Implementation
}

/**
 * Standalone function with extra param.
 *
 * @param string $name The name
 * @param int $nonexistent Doesn't exist
 */
function functionWithExtra(string $name): void
{
    // Implementation
}

/**
 * Standalone function with type mismatch.
 *
 * @param int $name Should be string
 */
function functionWithMismatch(string $name): void
{
    // Implementation
}

// Function without doc comment
function undocumentedFunction(string $name): void
{
    // Implementation
}
