<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Fixtures;

/**
 * Fixture for testing param order issues.
 */
class ParamOrderClass
{
    /**
     * Params in wrong order.
     *
     * @param string $second The second param
     * @param int $first The first param
     */
    public function wrongOrder(int $first, string $second): void
    {
    }

    /**
     * Params in correct order.
     *
     * @param int $first The first param
     * @param string $second The second param
     */
    public function correctOrder(int $first, string $second): void
    {
    }

    /**
     * Mixed wrong and missing.
     *
     * @param string $third The third param
     * @param int $first The first param
     */
    public function mixedIssues(int $first, string $second, string $third): void
    {
    }
}
