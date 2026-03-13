<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\WishlistService;

class RemoveFromWishlistMutation
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $this->wishlist->remove($sessionId, $args['article']);

        return [
            'success' => true,
            'count'   => $this->wishlist->count($sessionId),
        ];
    }
}
