<?php

declare(strict_types=1);

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Modules\Catalog\Models\Product;

class Promotion extends Model
{
    use HasTranslations, HasSlug, SoftDeletes;

    public array $translatable = [
        'title', 'banner_image', 'banner_image_mobile',
        'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'title', 'slug', 'banner_image', 'banner_image_mobile',
        'status', 'starts_at', 'ends_at',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (Promotion $model) => $model->getTranslation('title', 'ro'))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getDaysLeftAttribute(): ?int
    {
        if ($this->ends_at) {
            return max(0, (int) now()->diffInDays($this->ends_at, false));
        }

        return null;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_promotion')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }
}
