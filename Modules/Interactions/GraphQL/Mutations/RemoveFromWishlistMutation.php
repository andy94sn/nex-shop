<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use App\Models\Concerns\HasHashId;
use Modules\Catalog\Models\Product;
use Modules\Interactions\Services\WishlistService;

class RemoveFromWishlistMutation
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $productId = HasHashId::decodeHashId($args['id']);
        $product   = Product::findOrFail($productId);

        $sessionId = request()->session()->getId();
        $this->wishlist->remove($sessionId, $product->article);

        return [
            'success' => true,
            'count'   => $this->wishlist->count($sessionId),
        ];
    }
}
