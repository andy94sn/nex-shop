<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\WishlistService;
use Modules\Catalog\Models\Product;

class WishlistQuery
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $locale    = app()->getLocale();
        $articles  = $this->wishlist->get($sessionId);

        if (empty($articles)) {
            return [];
        }

        return Product::whereIn('article', $articles)
            ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->get()
            ->map(fn (Product $product) => [
                'id'                 => $product->id,
                'article'            => $product->article,
                'slug'               => $product->slug,
                'title'              => $product->getTranslation('title', $locale),
                'price'              => $product->price,
                'price_old'         => $product->price_old,
                'discount_percentage' => $product->discount_percentage,
                'is_new'             => $product->is_new,
                'thumbnail'          => $product->images->first()?->getFirstMediaUrl('product-images'),
                'is_in_wishlist'     => true,
                'is_in_cart'         => false,
            ])
            ->values()
            ->all();
    }
}
