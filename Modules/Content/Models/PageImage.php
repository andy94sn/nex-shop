<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class PageImage extends Model
{
    use HasTranslations;

    public array $translatable = ['alt', 'caption'];

    protected $fillable = [
        'page_id',
        'path',
        'alt',
        'caption',
        'is_cover',
        'sort',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'sort'     => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
