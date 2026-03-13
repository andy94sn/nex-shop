<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Modules\Commerce\Models\CreditPlan;

class Category extends Model
{
    use HasTranslations, HasTranslatableSlug, SoftDeletes;

    // ── Translatable ──────────────────────────────────────────────────────
    // 'title' shared with B2B (JSON). Shop adds description, meta fields.
    public array $translatable = [
        'title',
        'slug',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
    ];

    protected $fillable = [
        // ── B2B columns ───────────────────────────────────────────────────
        'status',           // tinyint 1=active / 0=inactive
        'code',             // unique category code used by B2B hierarchy
        'parent_code',      // B2B code-based parent reference
        'sort_order',       // B2B sort order
        'image',            // image filename
        'image_path',       // full storage path (B2B)

        // ── Shop columns ──────────────────────────────────────────────────
        'parent_id',
        'slug',
        'title',
        'short_description',
        'description',
        'featured_image',
        'is_active',
        'is_hidden_in_store',
        'is_hidden_in_menu',
        'is_featured',
        'featured_sort',
        'sort',
        'similar_categories',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'status'             => 'integer',
        'sort_order'         => 'integer',
        'is_active'          => 'boolean',
        'is_hidden_in_store' => 'boolean',
        'is_hidden_in_menu'  => 'boolean',
        'is_featured'        => 'boolean',
        'similar_categories' => 'array',
    ];

    // ── Slug ──────────────────────────────────────────────────────────────

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    // ── B2B-compatible helpers ────────────────────────────────────────────

    /**
     * Breadcrumb path string per locale (mirrors B2B getPathAttribute).
     */
    public function getPathAttribute(): array
    {
        $category = $this;
        $path = [];

        while ($category) {
            foreach ($category->getTranslations('title') as $locale => $translation) {
                $path[$locale][] = $translation;
            }
            $category = $category->parentCategory;
        }

        return array_map(
            fn ($parts) => implode(' > ', array_reverse($parts)),
            $path
        );
    }

    // ── Relations ─────────────────────────────────────────────────────────

    /** B2B-style: parent via code column. */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_code', 'code');
    }

    /** B2B-style: children via code column. */
    public function childCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_code', 'code');
    }

    /** Shop-style: parent via FK id. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Shop-style: children via FK id. */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->active();
    }

    /** B2B attribute groups scoped to this category. */
    public function attributeGroups(): HasMany
    {
        return $this->hasMany(AttributeGroup::class, 'category_id');
    }

    public function creditPlans(): BelongsToMany
    {
        return $this->belongsToMany(CreditPlan::class, 'category_credit_plan');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Active and not hidden in the storefront. */
    public function scopeVisibleInStore($query)
    {
        return $query->where('is_active', true)->where('is_hidden_in_store', false);
    }

    /** Active and not hidden in the navigation menu. */
    public function scopeVisibleInMenu($query)
    {
        return $query->where('is_active', true)->where('is_hidden_in_menu', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->orderBy('featured_sort');
    }

    /** Top-level categories only (no parent). */
    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Order by the display sort column. */
    public function scopeOrderedBySort($query)
    {
        return $query->orderBy('sort');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Return all descendant category IDs (children, grandchildren, …) plus
     * this category's own ID — using a single recursive CTE query so we never
     * hit N+1 regardless of tree depth.
     *
     * @return array<int>
     */
    public function descendantIds(): array
    {
        // One query with a recursive CTE — works on MySQL 8+ / MariaDB 10.2+.
        $rows = \Illuminate\Support\Facades\DB::select("
            WITH RECURSIVE cat_tree AS (
                SELECT id FROM categories WHERE id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT c.id FROM categories c
                INNER JOIN cat_tree ct ON c.parent_id = ct.id
                WHERE c.deleted_at IS NULL
            )
            SELECT id FROM cat_tree
        ", [$this->id]);

        return array_column($rows, 'id');
    }

    /** Mirrors B2B scopeWithDiscountedProducts. */
    public function scopeWithDiscountedProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('status', 1)
                ->whereNotNull('rrp_old')
                ->whereColumn('rrp_old', '>', 'rrp')
                ->where(function ($q) {
                    $q->whereNull('rrp_updated_at')
                        ->orWhere('rrp_updated_at', '>=', now()->subDays(20));
                });
        });
    }
}
