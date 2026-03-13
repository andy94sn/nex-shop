<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code'         => 'ro',
                'title'        => 'Romanian',
                'native_title' => 'Română',
                'flag'         => '🇷🇴',
                'is_active'    => true,
                'is_default'   => true,
                'sort'         => 1,
            ],
            [
                'code'         => 'ru',
                'title'        => 'Russian',
                'native_title' => 'Русский',
                'flag'         => '🇷🇺',
                'is_active'    => true,
                'is_default'   => false,
                'sort'         => 2,
            ],
            [
                'code'         => 'en',
                'title'        => 'English',
                'native_title' => 'English',
                'flag'         => '🇬🇧',
                'is_active'    => false,
                'is_default'   => false,
                'sort'         => 3,
            ],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang);
        }
    }
}
