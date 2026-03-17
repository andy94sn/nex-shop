<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Models\PaymentMethod;
use Modules\Core\Services\LocaleService;

class PaymentMethodsQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        return PaymentMethod::where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (PaymentMethod $m) => [
                'id'                   => $m->id,
                'code'                 => $m->code,
                'name'                 => $this->locale->trans($m, 'name'),
                'description'          => $this->locale->trans($m, 'description'),
                'icon'                 => $m->icon,
                'is_online'            => $m->is_online,
                'requires_credit_plan' => $m->requires_credit_plan,
            ])
            ->toArray();
    }
}
