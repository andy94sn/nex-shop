<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Queries\CheckoutTotalsQuery;
use Modules\Commerce\GraphQL\Concerns\ResolvesSessionId;

class SetDeliveryMethodMutation
{
    use ResolvesSessionId;

    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CheckoutTotalsQuery $totalsQuery,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $this->checkout->setDeliveryMethod($this->sessionId(), (int) $args['delivery_method_id']);

        return $this->totalsQuery->__invoke($root, $args, $context, $info);
    }
}
