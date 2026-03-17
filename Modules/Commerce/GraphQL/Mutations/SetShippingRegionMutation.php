<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Queries\CheckoutTotalsQuery;
use Modules\Commerce\GraphQL\Concerns\ResolvesSessionId;

class SetShippingRegionMutation
{
    use ResolvesSessionId;

    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CheckoutTotalsQuery $totalsQuery,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $this->checkout->setShipping(
            sessionId: $this->sessionId(),
            countryId: (int) $args['country_id'],
            cityId:    isset($args['city_id']) ? (int) $args['city_id'] : null,
            address:   isset($args['address']) ? (array) $args['address'] : null,
        );

        return $this->totalsQuery->__invoke($root, $args, $context, $info);
    }
}
