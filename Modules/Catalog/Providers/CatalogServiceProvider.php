<?php

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Console\GenerateSlugsCommand;
use Modules\Catalog\Console\SyncCategoryParentIdCommand;
use Modules\Catalog\Observers\CategoryObserver;
use Modules\Catalog\Observers\ProductObserver;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSlugsCommand::class,
                SyncCategoryParentIdCommand::class,
            ]);
        }

        Category::observe(CategoryObserver::class);
        Product::observe(ProductObserver::class);
    }
}
