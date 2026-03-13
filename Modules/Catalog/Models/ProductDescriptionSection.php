<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductDescriptionSection extends Model
{
    use HasTranslations;

    protected $table = 'product_description_sections';

    public array $translatable = ['title', 'content'];

    protected $fillable = ['product_id', 'title', 'content', 'image', 'sort'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
