<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single link entry shown in the footer (social, messenger, payment, etc.).
 * Written from the B2B panel; read-only in the shop.
 *
 * @property int         $id
 * @property string      $type    'social' | 'messenger' | 'payment' | any future type
 * @property string      $name    Display name (e.g. "Facebook", "Visa")
 * @property string|null $url     Destination URL or deep-link handle
 * @property string|null $icon    Image path or icon identifier
 * @property bool        $status  Whether the link is visible
 * @property int         $sort    Display order (ascending)
 */
class SiteLink extends Model
{
    protected $table = 'site_links';

    protected $fillable = ['type', 'name', 'url', 'icon', 'status', 'sort'];

    protected $casts = [
        'status' => 'boolean',
        'sort'   => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Return all active links of a given type, ordered, as plain arrays. */
    public static function activeOfType(string $type): array
    {
        return static::active()->ofType($type)->ordered()->get()->toArray();
    }
}
