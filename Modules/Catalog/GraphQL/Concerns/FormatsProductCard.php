<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Concerns;

use Modules\Catalog\Models\Product;

/**
 * Shared product-card formatter used by CategoryPageQuery, BrandPageQuery,
 * and SearchQuery.
 *
 * Requires the consuming class to have a `$this->locale` (LocaleService).
 */
trait FormatsProductCard
{
    private function formatProductCard(Product $p, array $wishlistItems, array $cartItems, string $locale): array
    {
        return [
            'id'                   => $p->id,
            'slug'                 => $this->locale->trans($p, 'slug'),
            'title'                => $this->locale->trans($p, 'title'),
            'subtitle'             => $this->locale->trans($p, 'subtitle'),
            'short_description'    => $this->locale->trans($p, 'short_description'),
            'description'          => $this->locale->trans($p, 'description'),
            'article'              => $p->article,
            'code'                 => $p->code,
            'image'                => $p->mainImage->first()?->path,
            'rrp'                  => $p->rrp,
            'rrp_old'              => $p->rrp_old,
            'stock'                => $p->stock,
            'is_new'               => $p->is_new,
            'discount_percentage'  => $p->discount_percentage,
            'best_credit_label'    => null,
            'is_in_wishlist'       => in_array($p->article, $wishlistItems, true),
            'is_in_cart'           => in_array($p->article, $cartItems, true),
            'brand'                => $p->brand ? ['id' => $p->brand->id, 'title' => $p->brand->title] : null,
            'variants'             => [],
            'description_sections' => [],
        ];
    }
}
