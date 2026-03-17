<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A single rate row inside a ShippingZone.
 *
 * Conditions (all nullable = wildcard / "match any"):
 *   delivery_method_id — specific delivery method, e.g. "shipping" or "pickup"
 *   payment_method_id  — specific payment method, e.g. "credit"
 *   cart_min / cart_max — cart-total range
 *
 * Geographic targeting is handled by the zone's country/city coverage,
 * NOT by a city string on the rate itself.
 */
class ShippingRate extends Model
{
    use HasTranslations;

    public array $translatable = ['label'];

    protected $fillable = [
        'shipping_zone_id',
        'delivery_method_id',
        'payment_method_id',
        'cart_min',
        'cart_max',
        'cost',
        'is_free',
        'label',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'cart_min'  => 'float',
        'cart_max'  => 'float',
        'cost'      => 'float',
        'is_free'   => 'boolean',
        'is_active' => 'boolean',
        'sort'      => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // ── Matching ──────────────────────────────────────────────────────────

    /**
     * Returns true when this rate's conditions match the given cart context.
     * Geographic matching (country/city) is done at the zone level in the
     * ShippingZoneService before rates are even considered.
     */
    public function matches(
        float $cartTotal,
        ?int $deliveryMethodId = null,
        ?int $paymentMethodId  = null,
    ): bool {
        if ($this->delivery_method_id !== null && $this->delivery_method_id !== $deliveryMethodId) {
            return false;
        }
        if ($this->payment_method_id !== null && $this->payment_method_id !== $paymentMethodId) {
            return false;
        }
        if ($this->cart_min !== null && $cartTotal < $this->cart_min) {
            return false;
        }
        if ($this->cart_max !== null && $cartTotal > $this->cart_max) {
            return false;
        }

        return true;
    }
}
