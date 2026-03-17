<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Represents a payment method (cash, COD, credit, bank, online gateway, etc.).
 *
 * @property string      $code                  Unique code used in business logic
 * @property bool        $is_online             Triggers an online payment/gateway flow
 * @property string|null $gateway               Gateway driver name ('maib', 'stripe', …)
 * @property array|null  $gateway_config        Arbitrary gateway config stored as JSON
 * @property bool        $requires_credit_plan  Whether a credit plan must be selected
 */
class PaymentMethod extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'is_online',
        'gateway',
        'gateway_config',
        'requires_credit_plan',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_online'            => 'boolean',
        'gateway_config'       => 'array',
        'requires_credit_plan' => 'boolean',
        'is_active'            => 'boolean',
        'sort'                 => 'integer',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Delivery methods that are compatible with this payment method.
     * If the pivot table has no rows for this payment method, ALL delivery
     * methods are considered compatible (open / unrestricted).
     */
    public function deliveryMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            DeliveryMethod::class,
            'payment_method_delivery_method',
        );
    }
}
