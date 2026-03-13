<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'status', 'payment_method',
        'credit_plan_id', 'credit_extras_selected',
        'contact_name', 'contact_email', 'contact_phone',
        'shipping_region_id', 'shipping_address',
        'subtotal', 'discount', 'shipping_cost', 'total',
        'coupon_id', 'coupon_discount',
        'id_card_front', 'id_card_back', 'birth_date', 'idnp',
        'notes',
    ];

    protected $casts = [
        'credit_extras_selected' => 'array',
        'subtotal'               => 'float',
        'discount'               => 'float',
        'shipping_cost'          => 'float',
        'total'                  => 'float',
        'coupon_discount'        => 'float',
        'birth_date'             => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creditPlan(): BelongsTo
    {
        return $this->belongsTo(CreditPlan::class);
    }

    public function shippingRegion(): BelongsTo
    {
        return $this->belongsTo(ShippingRegion::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
