<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Concerns;

use Modules\Catalog\Models\Brand;

/**
 * Brand formatting helpers shared by BrandsQuery and FeaturedBrandsQuery.
 *
 * Requires the consuming class to have a `$this->locale` (LocaleService).
 */
trait FormatsBrandData
{
    private function formatBrand(Brand $brand, string $locale): array
    {
        return [
            'id'             => $brand->id,
            'slug'           => $brand->getTranslation('slug', $locale, false),
            'title'          => $brand->title,
            'meta_title'     => $brand->getTranslation('meta_title', $locale, false),
            'logo'           => $brand->logo,
            'image'          => $brand->image,
            'products_count' => $brand->activeProducts()->count(),
            'is_featured'    => $brand->is_featured,
            'featured_sort'  => $brand->featured_sort,
        ];
    }
}
