<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Concerns;

/**
 * Tiny helper trait to format monetary/totals values consistently.
 * Uses a single place to control rounding to two decimals.
 */
trait FormatsTotals
{
    /**
     * Format a value as a monetary amount rounded to 2 decimal places.
     * Always returns a float.
     *
     * @param mixed $value
     */
    protected function formatAmount(mixed $value): float
    {
        return round((float) ($value ?? 0.0), 2);
    }
}
