<?php

declare(strict_types=1);

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ContactDepartment extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'address'];

    protected $fillable = [
        'title', 'address', 'phone', 'email',
        'schedule_mon_fri', 'schedule_sat', 'schedule_sun', 'schedule_break',
        'sort', 'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'schedule_mon_fri' => 'array',
        'schedule_sat'     => 'array',
        'schedule_sun'     => 'array',
        'schedule_break'   => 'array',
    ];
}
