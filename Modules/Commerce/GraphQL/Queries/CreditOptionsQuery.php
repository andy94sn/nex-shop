<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\Models\Product;
use Modules\Commerce\Models\CreditExtra;

class CreditOptionsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $product = Product::with(['creditPlans' => fn ($q) => $q->where('is_active', true)->orderBy('months')])
            ->where('slug', $args['product_slug'])
            ->firstOrFail();

        // Product override has priority; else fall back to category plans
        $plans = $product->creditPlans->isEmpty()
            ? $product->category->creditPlans()->where('is_active', true)->orderBy('months')->get()
            : $product->creditPlans;

        $formattedPlans = $plans->map(fn ($plan) => [
            'id'               => $plan->id,
            'months'           => $plan->months,
            'interest_label'   => $plan->interest_label,
            'monthly_rate'     => $plan->monthlyRate($product->rrp),
            'total'            => round($plan->monthlyRate($product->rrp) * $plan->months, 2),
            'is_zero_interest' => $plan->is_zero_interest,
        ])->toArray();

        $creditMinRate = ! empty($formattedPlans)
            ? min(array_column($formattedPlans, 'monthly_rate'))
            : null;

        return [
            'plans'           => $formattedPlans,
            'extras'          => CreditExtra::where('is_active', true)->get()->toArray(),
            'credit_min_rate' => $creditMinRate,
        ];
    }
}
