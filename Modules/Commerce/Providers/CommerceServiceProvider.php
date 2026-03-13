<?php

declare(strict_types=1);

namespace Modules\Commerce\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Services\CheckoutService;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CheckoutService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'commerce');
    }
}
