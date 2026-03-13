<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Queries\CheckoutTotalsQuery;

class SetPaymentMethodMutation
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CheckoutTotalsQuery $totalsQuery,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $this->checkout->setPaymentMethod($sessionId, $args['method']);

        return $this->totalsQuery->__invoke($root, $args, $context, $info);
    }
}
