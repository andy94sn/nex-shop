<?php

declare(strict_types=1);

namespace Modules\Interactions\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Interactions\Services\CartService;
use Modules\Interactions\Services\WishlistService;
use Modules\Interactions\Services\CompareService;

class InteractionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
        $this->app->singleton(WishlistService::class);
        $this->app->singleton(CompareService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
