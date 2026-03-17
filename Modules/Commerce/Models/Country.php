<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Modules\Commerce\Models\City;
use Modules\Commerce\Models\ShippingZone;

class Country extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['code', 'name', 'is_active', 'sort'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort'      => 'integer',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function shippingZones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'shipping_zone_countries');
    }
}
