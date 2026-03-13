<?php

declare(strict_types=1);

namespace Modules\Catalog\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Models\Category;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        // Invalidate navigation menu cache on any category change (Task 6)
        Cache::forget('shop.menu.categories');
        Cache::forget('shop.featured.categories');
    }

    public function deleted(Category $category): void
    {
        Cache::forget('shop.menu.categories');
        Cache::forget('shop.featured.categories');
    }
}
