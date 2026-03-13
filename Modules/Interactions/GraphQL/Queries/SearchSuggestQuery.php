<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\Models\Product;

/**
 * Quick-search suggest: returns up to `limit` products per call,
 * grouped by top-level category name (Task 16a).
 */
class SearchSuggestQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $query  = trim($args['query']);
        $limit  = (int) ($args['limit'] ?? 4);
        $locale = app()->getLocale();

        if (strlen($query) < 2) {
            return [];
        }

        $products = Product::search($query)
            ->take(max(12, $limit * 4))
            ->get()
            ->load(['category', 'images' => fn ($q) => $q->orderBy('position')->limit(1)]);

        // Group by category
        $groups = [];
        foreach ($products as $product) {
            $catName = $product->category?->getTranslation('name', $locale) ?? 'Altele';

            if (! isset($groups[$catName])) {
                $groups[$catName] = [];
            }

            if (count($groups[$catName]) < $limit) {
                $groups[$catName][] = [
                    'id'      => $product->id,
                    'article' => $product->article,
                    'slug'    => $product->slug,
                    'title'   => $product->getTranslation('title', $locale),
                    'price'   => $product->price,
                    'thumbnail' => $product->images->first()?->getFirstMediaUrl('product-images'),
                ];
            }
        }

        return array_map(
            fn ($category, $items) => ['category' => $category, 'products' => $items],
            array_keys($groups),
            array_values($groups),
        );
    }
}
