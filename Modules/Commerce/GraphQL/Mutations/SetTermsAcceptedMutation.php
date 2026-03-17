<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Modules\Commerce\GraphQL\Concerns\BuildsCartResult;
use Modules\Commerce\Services\CheckoutService;
use Modules\Interactions\Services\CartService;

class SetTermsAcceptedMutation
{
    use BuildsCartResult;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $this->checkout->setTermsAccepted($this->sessionId(), (bool) $args['accepted']);

        return $this->buildCartResult($this->sessionId());
    }
}
