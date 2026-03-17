<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Modules\Commerce\Models\Country;
use Modules\Commerce\Models\City;
use Modules\Commerce\Models\ShippingRate;

/**
 * A shipping zone groups geographic coverage (countries and/or specific cities)
 * together with a set of ShippingRates.
 *
 * Coverage logic (WooCommerce-style):
 *   - If the zone has city-level entries, it applies to those cities only.
 *   - If the zone has country-level entries (and no city restriction), it applies
 *     to the entire country.
 *   - A zone with no geographic entries is a catch-all ("Everywhere else").
 */
class ShippingZone extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'default_cost', 'is_active', 'sort'];

    protected $casts = [
        'default_cost' => 'float',
        'is_active'    => 'boolean',
        'sort'         => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    /** Countries covered by this zone (whole-country coverage). */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'shipping_zone_countries');
    }

    /** Specific cities covered by this zone (city-level override). */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'shipping_zone_cities');
    }

    /** Shipping rates belonging to this zone, ordered by priority. */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class)
            ->where('is_active', true)
            ->orderBy('sort');
    }
}
