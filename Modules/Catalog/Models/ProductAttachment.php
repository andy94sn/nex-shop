<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductAttachment extends Model
{
    use HasTranslations;

    protected $table = 'product_files';

    public array $translatable = ['title'];

    protected $fillable = [
        'product_id', 'filename', 'path', 'title',
        'file_type', 'file_size', 'locale',
        'is_presentation', 'sort_order',
    ];

    protected $attributes = [
        'locale' => 'ro',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
