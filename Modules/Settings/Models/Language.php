<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    protected $fillable = [
        'code', 'title', 'native_title', 'flag',
        'is_active', 'is_default', 'sort',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'sort'       => 'integer',
    ];

    /** All active languages ordered by sort, cached. */
    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('languages.active', 3600, fn () =>
            static::where('is_active', true)->orderBy('sort')->get()
        );
    }

    /** The default language (falls back to first active). */
    public static function default(): self
    {
        return Cache::remember('languages.default', 3600, fn () =>
            static::where('is_default', true)->first()
                ?? static::where('is_active', true)->orderBy('sort')->firstOrFail()
        );
    }

    /** Resolve the locale from the session, or fall back to default. */
    public static function resolveLocale(): string
    {
        $session = request()->hasSession() ? request()->session() : null;
        $code    = $session?->get('locale');

        if ($code && static::active()->contains('code', $code)) {
            return $code;
        }

        return static::default()->code;
    }
}
