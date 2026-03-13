<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductVariant extends Model
{
    use HasTranslations;

    public array $translatable = ['color_label'];

    protected $fillable = [
        'product_id', 'linked_article', 'color_value', 'color_label', 'option', 'sort',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
