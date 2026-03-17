<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class PageAttachment extends Model
{
    use HasTranslations;

    public array $translatable = ['label'];

    protected $fillable = [
        'page_id',
        'path',
        'label',
        'mime_type',
        'size',
        'sort',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
