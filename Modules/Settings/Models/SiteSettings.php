<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SiteSettings extends Model
{
    use HasTranslations;

    protected $table = 'site_settings';

    public array $translatable = [
        'contact_address',
        'seo_title',
        'seo_description',
        'footer_text',
    ];

    protected $casts = [];

    protected $fillable = [
        'site_name', 'site_logo', 'site_favicon',
        'contact_email', 'contact_phone', 'contact_address',
        'terms_page_id',
        'currency_symbol', 'currency_code', 'default_locale',
        'seo_title', 'seo_description', 'seo_og_image',
        'footer_text', 'footer_logo', 'footer_logo_dark',
    ];

    /** Always use the single row pattern. */
    public static function instance(): self
    {
        return static::firstOrCreate(['id' => 1], ['site_name' => 'Nex Distribution']);
    }
}
