<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // ── Modules ─────────────────────────────────────────────────────────────
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Settings\Providers\SettingsServiceProvider::class,
    Modules\Catalog\Providers\CatalogServiceProvider::class,
    Modules\Content\Providers\ContentServiceProvider::class,
    Modules\Commerce\Providers\CommerceServiceProvider::class,
    Modules\Interactions\Providers\InteractionsServiceProvider::class,
    Modules\Marketing\Providers\MarketingServiceProvider::class,
];
