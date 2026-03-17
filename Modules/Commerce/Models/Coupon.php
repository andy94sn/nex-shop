<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Commerce\GraphQL\Concerns\FormatsTotals;

class Coupon extends Model
{
    use FormatsTotals;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'value'           => 'float',
        'min_order_value' => 'float',
        'valid_from'      => 'datetime',
        'valid_until'     => 'datetime',
    ];

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;

        return true;
    }

    /**
     * Whether the coupon's minimum order requirement is satisfied.
     * Always true when no min_order_value is set.
     */
    public function meetsMinOrder(float $subtotal): bool
    {
        if (! $this->min_order_value) return true;

        return $subtotal >= (float) $this->min_order_value;
    }

    /**
     * Calculate the discount amount for a given subtotal.
     * Returns 0.0 if the coupon is invalid or min order is not met.
     */
    public function discountFor(float $subtotal): float
    {
        if (! $this->isValid() || ! $this->meetsMinOrder($subtotal)) {
            return 0.0;
        }

        return match ($this->type) {
            'percentage' => $this->formatAmount($subtotal * $this->value / 100),
            'fixed'      => $this->formatAmount(min($this->value, $subtotal)),
            default      => 0.0,
        };
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }
}
