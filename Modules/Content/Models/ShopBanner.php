<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ShopBanner extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'image', 'image_mobile'];

    protected $fillable = [
        'title', 'image', 'image_mobile', 'url',
        'sort', 'is_active', 'valid_from', 'valid_until',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function scopeActiveAndValid($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
            ->orderBy('sort');
    }
}
