<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\WishlistService;

class WishlistCountQuery
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): int
    {
        return $this->wishlist->count(request()->session()->getId());
    }
}
