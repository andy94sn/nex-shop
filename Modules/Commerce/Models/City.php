<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Modules\Commerce\Models\Country;
use Modules\Commerce\Models\ShippingZone;

class City extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['country_id', 'name', 'is_active', 'sort'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort'      => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function shippingZones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'shipping_zone_cities');
    }
}
