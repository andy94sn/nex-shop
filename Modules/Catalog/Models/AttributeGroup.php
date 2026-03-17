<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use App\Models\Concerns\HasHashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class AttributeGroup extends Model
{
    use HasHashId, HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = ['category_id', 'title', 'image', 'is_filter', 'sort'];

    protected $casts = ['is_filter' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class)->orderBy('sort');
    }
}
