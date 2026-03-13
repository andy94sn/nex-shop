<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Variable;

class VariableSeeder extends Seeder
{
    public function run(): void
    {
        $variables = [
            // ── Example entries — uncomment and fill in as needed ─────────
            //
            // ['group' => 'seo',      'key' => 'default_title',       'value' => 'NexDistribution Shop',       'is_json' => false],
            // ['group' => 'seo',      'key' => 'default_description',  'value' => 'Buy electronics online.',    'is_json' => false],
            //
            // ['group' => 'contact',  'key' => 'phone',                'value' => '+373 22 000 000',            'is_json' => false],
            // ['group' => 'contact',  'key' => 'email',                'value' => 'info@nexdistribution.md',   'is_json' => false],
            // ['group' => 'contact',  'key' => 'address',              'value' => 'Chișinău, Moldova',          'is_json' => false],
            //
            // ['group' => 'homepage', 'key' => 'banner_slides',        'value' => '[]',                         'is_json' => true],
            //
            ['group' => 'map', 'key' => 'geo_location','value' => '','is_json' => false]
        ];

        foreach ($variables as $variable) {
            Variable::firstOrCreate(
                ['group' => $variable['group'], 'key' => $variable['key']],
                ['value' => $variable['value'], 'is_json' => $variable['is_json']]
            );
        }
    }
}
