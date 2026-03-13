<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Variable;

/**
 * Read-only access to the `variables` table.
 *
 * Usage:
 *   Variables::get('homepage', 'phone')          // returns string|null
 *   Variables::get('seo', 'scripts', [])          // returns mixed, default [] if missing
 *   Variables::group('homepage')                  // returns ['key' => value, ...]
 *   Variables::flush()                            // clear cached variables
 */
class Variables
{
    private const TTL = 3600; // seconds

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Get a single variable value.
     *
     * @param  string $group   The variable group (e.g. "homepage")
     * @param  string $key     The variable key (e.g. "phone")
     * @param  mixed  $default Returned when the variable does not exist
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $all = static::loadGroup($group);

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Get all variables for a group as an associative array: ['key' => value, ...].
     */
    public static function group(string $group): array
    {
        return static::loadGroup($group);
    }

    /**
     * Flush the in-memory and cache layer for one or all groups.
     */
    public static function flush(?string $group = null): void
    {
        if ($group) {
            Cache::forget(static::cacheKey($group));
        } else {
            // Flush every cached group by tagging is not available on all drivers,
            // so we just clear all variable cache keys we know about.
            $groups = Variable::query()->distinct()->pluck('group');
            foreach ($groups as $g) {
                Cache::forget(static::cacheKey($g));
            }
        }
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private static function loadGroup(string $group): array
    {
        return Cache::remember(
            static::cacheKey($group),
            static::TTL,
            static function () use ($group): array {
                return Variable::where('group', $group)
                    ->get()
                    ->mapWithKeys(fn (Variable $v) => [$v->key => $v->getValue()])
                    ->toArray();
            }
        );
    }

    private static function cacheKey(string $group): string
    {
        return "variables.{$group}";
    }
}
