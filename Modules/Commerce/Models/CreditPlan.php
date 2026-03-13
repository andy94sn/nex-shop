<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CreditPlan extends Model
{
    protected $fillable = [
        'months', 'interest_rate', 'interest_label', 'is_active', 'sort',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'interest_rate' => 'float',
        'months'        => 'integer',
    ];

    public function getIsZeroInterestAttribute(): bool
    {
        return $this->interest_rate == 0;
    }

    /** Calculate monthly rate for a given price. */
    public function monthlyRate(float $price): float
    {
        if ($this->interest_rate == 0) {
            return round($price / $this->months, 2);
        }

        // Standard annuity formula
        $r = $this->interest_rate / 100 / 12;
        $n = $this->months;

        return round($price * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1), 2);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_credit_plan');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'credit_plan_product');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('months');
    }
}
