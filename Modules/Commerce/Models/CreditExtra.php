<?php

declare(strict_types=1);

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CreditExtra extends Model
{
    use HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = [
        'title', 'price_per_month', 'available_durations', 'is_active',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'price_per_month'     => 'float',
        'available_durations' => 'array',
    ];
}
