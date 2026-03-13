<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Page extends Model
{
    use HasTranslations, HasSlug, SoftDeletes;

    public array $translatable = [
        'title', 'content', 'intro_text',
        'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'slug', 'title', 'content', 'is_active', 'type',
        'meta_title', 'meta_description',
        'intro_text', 'map_lat', 'map_lng',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'map_lat'   => 'float',
        'map_lng'   => 'float',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (Page $model) => $model->getTranslation('title', 'ro'))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }
}
