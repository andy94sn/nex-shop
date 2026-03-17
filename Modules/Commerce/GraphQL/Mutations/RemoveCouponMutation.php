<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Concerns\BuildsCartResult;
use Modules\Interactions\Services\CartService;

class RemoveCouponMutation
{
    use BuildsCartResult;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $this->checkout->removeCoupon($this->sessionId());

        return $this->buildCartResult($this->sessionId());
    }
}
