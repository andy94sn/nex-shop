<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CartService;
use Modules\Commerce\GraphQL\Queries\CartQuery;

class UpdateCartQuantityMutation
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CartQuery $cartQuery,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $this->cart->updateQuantity($sessionId, $args['article'], $args['quantity']);

        return $this->cartQuery->__invoke($root, $args, $context, $info);
    }
}
