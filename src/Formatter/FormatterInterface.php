<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Formatter;

use NsRosenqvist\PhpDocValidator\Report;

/**
 * Interface for report formatters.
 */
interface FormatterInterface
{
    /**
     * Format a validation report as a string.
     */
    public function format(Report $report, ?string $basePath = null): string;
}
