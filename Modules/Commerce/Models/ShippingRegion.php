<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ShippingRegion extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'shipping_cost', 'is_active', 'sort'];

    protected $casts = [
        'is_active'     => 'boolean',
        'shipping_cost' => 'float',
    ];
}
