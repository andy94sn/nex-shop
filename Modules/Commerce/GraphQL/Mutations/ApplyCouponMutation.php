<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Queries\CheckoutTotalsQuery;
use GraphQL\Error\UserError;

class ApplyCouponMutation
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CheckoutTotalsQuery $totalsQuery,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();

        try {
            $this->checkout->applyCoupon($sessionId, $args['code']);
        } catch (\Exception $e) {
            throw new UserError($e->getMessage());
        }

        return $this->totalsQuery->__invoke($root, $args, $context, $info);
    }
}
