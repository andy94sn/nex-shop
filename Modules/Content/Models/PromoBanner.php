<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PromoBanner extends Model
{
    use HasTranslations;

    public array $translatable = [
        'image', 'image_mobile', 'title', 'subtitle', 'description', 'button_text',
    ];

    protected $fillable = [
        'image', 'image_mobile', 'url',
        'title', 'subtitle', 'description', 'button_text',
        'expires_at', 'is_active', 'valid_from', 'valid_until',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'expires_at'  => 'datetime',
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function scopeActiveAndValid($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
    }
}
