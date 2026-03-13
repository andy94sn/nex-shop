<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared product-list filters and sort logic used by CategoryPageQuery,
 * BrandPageQuery, and SearchQuery.
 */
trait AppliesProductFilters
{
    /**
     * Apply the standard product listing filters.
     *
     * @param  array<int>  $allCategoryIds  The full set of valid category IDs
     *                                      for the current context (used to
     *                                      prevent leaking products outside a
     *                                      category tree). Pass an empty array
     *                                      to skip the category intersection
     *                                      guard (e.g. on a brand page or
     *                                      search page).
     */
    private function applyFilters(Builder $query, array $filters, string $locale, array $allCategoryIds = []): void
    {
        if (! empty($filters['brand_ids'])) {
            $query->whereIn('brand_id', $filters['brand_ids']);
        }
        if (isset($filters['price_min'])) {
            $query->where('rrp', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('rrp', '<=', $filters['price_max']);
        }
        if (! empty($filters['credit_plan_ids'])) {
            $query->whereHas('creditPlans', fn ($q) =>
                $q->whereIn('credit_plans.id', $filters['credit_plan_ids'])
            );
        }
        if (! empty($filters['category_ids'])) {
            $allowed = empty($allCategoryIds)
                ? array_map('intval', $filters['category_ids'])
                : array_intersect($allCategoryIds, array_map('intval', $filters['category_ids']));

            if (! empty($allowed)) {
                $query->whereIn('category_id', $allowed);
            }
        }
        if (! empty($filters['attributes'])) {
            foreach ($filters['attributes'] as $af) {
                $query->whereHas('attributeValues', fn ($q) =>
                    $q->whereIn('attribute_value_id', $af['value_ids'])
                );
            }
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'PRICE_ASC'  => $query->orderBy('rrp'),
            'PRICE_DESC' => $query->orderByDesc('rrp'),
            'NEWEST'     => $query->orderByDesc('created_at'),
            default      => $query->orderedBySort(),
        };
    }
}
