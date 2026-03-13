<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | All locales the application supports. The first one is the default.
    */
    'locales' => explode(',', env('APP_SUPPORTED_LOCALES', 'ro,ru')),

    'default_locale' => env('APP_LOCALE', 'ro'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ro'),
];
