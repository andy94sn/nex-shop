<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Modules\Commerce\Models\CreditPlan;

class Product extends Model
{
    use HasTranslations, HasTranslatableSlug, SoftDeletes, Searchable;

    // ── Translatable ──────────────────────────────────────────────────────
    // 'title' and 'description' are JSON columns shared with B2B.
    public array $translatable = [
        'title',
        'slug',
        'subtitle',
        'short_description',
        'description',       // B2B full description (JSON)
        'meta_title',
        'meta_description',
    ];

    protected $fillable = [
        // ── B2B columns ───────────────────────────────────────────────────
        'external_id',        // bigint – B2B primary key reference
        'status',             // tinyint 1=active / 0=inactive (B2B)
        'article',            // product article / SKU
        'code',               // short code
        'description',        // JSON translatable full description (B2B)
        'rrp',                // recommended retail price
        'rrp_old',            // old RRP for discount display
        'rrp_updated_at',     // B2B: nullify rrp_old discount after 20 days
        'price_euro',         // B2B price in EUR
        'price_euro_old',     // B2B old EUR price (for % badge)
        'price_usd',          // B2B price in USD
        'quantity',           // B2B stock quantity
        'weight',             // grams
        'height',             // mm
        'length',             // mm
        'width',              // mm
        'preorder',           // tinyint – available for pre-order
        'youtube_url',        // B2B video link
        'is_new_until',       // date: marks product as "new" until this date

        // ── Shop columns ──────────────────────────────────────────────────
        'category_id',
        'brand_id',
        'slug',
        'title',
        'subtitle',
        'short_description',
        'price_eur',          // shop-specific EUR price (legacy alias)
        'stock',              // shop-specific stock (legacy alias)
        'is_active',          // shop active flag (kept for backward compat)
        'is_new',             // manual "new" flag
        'youtube_url',   // shop video URL (legacy alias)
        'sort',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        // B2B
        'external_id'    => 'integer',
        'status'         => 'integer',
        'preorder'       => 'boolean',
        'price_euro'     => 'float',
        'price_euro_old' => 'float',
        'price_usd'      => 'float',
        'quantity'       => 'integer',
        'weight'         => 'integer',
        'height'         => 'integer',
        'length'         => 'integer',
        'width'          => 'integer',
        'rrp_updated_at' => 'datetime',
        // Shared / shop
        'is_active'      => 'boolean',
        'is_new'         => 'boolean',
        'is_new_until'   => 'date',
        'rrp'            => 'float',
        'rrp_old'        => 'float',
        'price_eur'      => 'float',
    ];

    // ── Slug ──────────────────────────────────────────────────────────────

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    // ── Scout / Search ────────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id'            => $this->id,
            'title_ro'      => $this->getTranslation('title', 'ro'),
            'title_ru'      => $this->getTranslation('title', 'ru'),
            'short_desc_ro' => $this->getTranslation('short_description', 'ro'),
            'short_desc_ru' => $this->getTranslation('short_description', 'ru'),
            'article'       => $this->article,
            'code'          => $this->code,
        ];
    }

    // ── Computed attributes ────────────────────────────────────────────────

    /**
     * True if status=1 OR is_active=true (backward compat with both schemas).
     */
    public function getIsActiveAttribute(): bool
    {
        // If the B2B status column is set, it takes precedence.
        if (isset($this->attributes['status'])) {
            return (bool) $this->attributes['status'];
        }

        return (bool) ($this->attributes['is_active'] ?? false);
    }

    /**
     * "New" badge: manual flag OR is_new_until date is in the future.
     */
    public function getIsNewAttribute(): bool
    {
        if (!empty($this->attributes['is_new'])) {
            return true;
        }

        if ($this->is_new_until) {
            return $this->is_new_until->isFuture();
        }

        return false;
    }

    /**
     * B2B rule: rrp_old is only shown if rrp_updated_at is within 20 days.
     * Returns rrp_old or null.
     */
    public function getRrpOldValueAttribute(): ?float
    {
        if (!$this->rrp_old) {
            return null;
        }

        if ($this->rrp_updated_at && $this->rrp_updated_at->lt(now()->subDays(20))) {
            return null;
        }

        return $this->rrp_old;
    }

    /**
     * Percentage discount on RRP (using 20-day rule for rrp_old).
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        $rrpOld = $this->rrp_old_value; // uses 20-day rule
        if ($rrpOld && $this->rrp > 0) {
            return (int) round((($rrpOld - $this->rrp) / $rrpOld) * 100);
        }

        return null;
    }

    /**
     * Percentage discount on Euro price (B2B badge).
     */
    public function getPriceEuroDiscountAttribute(): ?int
    {
        if ($this->price_euro_old && $this->price_euro > 0) {
            return (int) round(
                (($this->price_euro_old - $this->price_euro) / $this->price_euro_old) * 100
            );
        }

        return null;
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->main();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProductAttachment::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort');
    }

    public function descriptionSections(): HasMany
    {
        return $this->hasMany(ProductDescriptionSection::class)->orderBy('sort');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function creditPlans(): BelongsToMany
    {
        return $this->belongsToMany(CreditPlan::class, 'credit_plan_product');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /** Active products — shop flag (is_active) takes precedence; B2B status=1 also counts. */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_active', true)
              ->orWhere('status', 1);
        });
    }

    /** Products that currently have a visible discount on RRP. */
    public function scopeOnlyDiscounted($query)
    {
        return $query->whereNotNull('rrp_old')
            ->whereColumn('rrp_old', '>', 'rrp')
            ->where(function ($q) {
                $q->whereNull('rrp_updated_at')
                  ->orWhere('rrp_updated_at', '>=', now()->subDays(20));
            });
    }

    /** Products available for purchase (quantity > 0 OR stock > 0). */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('quantity', '>', 0)
              ->orWhere('stock', '>', 0);
        });
    }

    /**
     * Full-text search across article, code and translated title.
     * Matches any product whose article, code, or title (in $locale) contains $term.
     */
    public function scopeSearch($query, string $term, string $locale)
    {
        return $query->where(function ($q) use ($term, $locale) {
            $q->where('article', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere("title->{$locale}", 'like', "%{$term}%");
        });
    }

    /** Order by the display sort column (default storefront ordering). */
    public function scopeOrderedBySort($query)
    {
        return $query->orderBy('sort');
    }
}
