<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\FormatsProductCard;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;
use Modules\Interactions\Services\CartService;
use Modules\Interactions\Services\WishlistService;

class WishlistQuery
{
    use FormatsProductCard;

    public function __construct(
        private readonly LocaleService   $locale,
        private readonly WishlistService $wishlist,
        private readonly CartService     $cart,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $locale    = $this->locale->get();
        $articles  = $this->wishlist->get($sessionId);

        // Always return the full WishlistResult shape expected by the schema.
        if (empty($articles)) {
            return [
                'items'        => [],
                'total'        => 0,
                'current_page' => $args['page'] ?? 1,
                'last_page'    => 1,
            ];
        }

        $perPage = $args['perPage'] ?? 24;
        $page    = $args['page'] ?? 1;

        $wishlistItems = $this->wishlist->get($sessionId);
        $cartItems     = array_keys($this->cart->get($sessionId));

        $products = Product::whereIn('article', $articles)
            ->with(['mainImage', 'brand'])
            ->get();

        $items = $products
            ->map(fn (Product $p) => $this->formatProductCard($p, $wishlistItems, $cartItems, $locale))
            ->values()
            ->all();

        $total    = count($items);
        $lastPage = (int) max(1, ceil($total / max(1, $perPage)));

        return [
            'items'        => $items,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
        ];
    }
}
