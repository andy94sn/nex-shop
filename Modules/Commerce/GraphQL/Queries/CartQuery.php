<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CartService;
use Modules\Catalog\Models\Product;

class CartQuery
{
    public function __construct(private readonly CartService $cart) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $items     = $this->cart->get($sessionId);
        $subtotal  = 0.0;
        $formatted = [];

        foreach ($items as $item) {
            $product = Product::with(['images' => fn ($q) => $q->where('is_main', true)])
                ->where('article', $item['article'])
                ->first();

            $currentPrice = $product?->rrp ?? $item['price'];
            $subtotal    += $item['price'] * $item['quantity'];

            $formatted[] = [
                'article'         => $item['article'],
                'quantity'        => $item['quantity'],
                'snapshot_price'  => $item['price'],
                'snapshot_title'  => $item['title'][app()->getLocale()] ?? reset($item['title']),
                'snapshot_image'  => $item['image'],
                'current_price'   => $currentPrice,
                'current_stock'   => $product?->stock,
                'current_title'   => $product?->title,
                'current_image'   => $product?->images->first()?->path,
                'rrp_old'         => $product?->rrp_old,
                'is_unavailable'  => ! $product || ! $product->is_active || $product->stock === 0,
                'stock_changed'   => $product && $product->stock > 0 && $product->stock < $item['quantity'],
            ];
        }

        return [
            'items'    => $formatted,
            'subtotal' => round($subtotal, 2),
            'count'    => $this->cart->count($sessionId),
        ];
    }
}
