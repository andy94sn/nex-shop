<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/core.php',
            'core'
        );
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware(
            'locale',
            \Modules\Core\Http\Middleware\SetLocaleMiddleware::class
        );

        $this->app['router']->aliasMiddleware(
            'token.session',
            \Modules\Core\Http\Middleware\TokenSessionMiddleware::class
        );
    }
}
