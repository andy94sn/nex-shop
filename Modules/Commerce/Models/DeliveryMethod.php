<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Represents a delivery method (shipping, pickup, etc.).
 *
 * @property string $code              Unique code used in business logic
 * @property bool   $requires_address  Whether a shipping address must be collected
 */
class DeliveryMethod extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'requires_address',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'requires_address' => 'boolean',
        'is_active'        => 'boolean',
        'sort'             => 'integer',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Payment methods that are compatible with this delivery method.
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            PaymentMethod::class,
            'payment_method_delivery_method',
        );
    }
}
