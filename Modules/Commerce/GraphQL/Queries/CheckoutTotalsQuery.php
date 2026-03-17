<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\GraphQL\Concerns\ResolvesSessionId;

class CheckoutTotalsQuery
{
    use ResolvesSessionId;

    public function __construct(private readonly CheckoutService $checkout) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        return $this->checkout->calculateTotals($this->sessionId());
    }
}
