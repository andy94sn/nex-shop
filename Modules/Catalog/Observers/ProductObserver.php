<?php

declare(strict_types=1);

namespace Modules\Catalog\Observers;

use Modules\Catalog\Models\Product;

class ProductObserver
{
    public function saving(Product $product): void
    {
        // Auto-generate meta_title from title if empty (Task 3)
        foreach (config('core.locales', ['ro', 'ru']) as $locale) {
            $metaTitle = $product->getTranslation('meta_title', $locale, false);

            if (empty($metaTitle)) {
                $product->setTranslation(
                    'meta_title',
                    $locale,
                    $product->getTranslation('title', $locale, false) ?? ''
                );
            }
        }
    }
}
