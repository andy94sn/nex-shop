<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use App\Models\Concerns\HasHashId;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;

/**
 * Quick-search suggest: returns up to `limit` products per call,
 * grouped by top-level category name (Task 16a).
 */
class SearchSuggestQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $term   = trim($args['query']);
        $limit  = (int) ($args['limit'] ?? 4);

        if (strlen($term) < 2) {
            return [];
        }

        $products = Product::search($term)
            ->take(max(12, $limit * 4))
            ->get()
            ->load(['category', 'mainImage']);

        // Group by translated category title
        $groups = [];
        foreach ($products as $p) {
            $catTitle = $p->category
                ? $this->locale->trans($p->category, 'title')
                : 'Altele';

            if (! isset($groups[$catTitle])) {
                $groups[$catTitle] = [];
            }

            if (count($groups[$catTitle]) < $limit) {
                $groups[$catTitle][] = [
                    'id'      => HasHashId::hashId($p->id),
                    'title'   => $this->locale->trans($p, 'title'),
                    'image'   => $p->mainImage->first()?->path,
                    'article' => $p->article,
                    'slug'    => $this->locale->trans($p, 'slug'),
                    'rrp'     => $p->rrp,
                    'rrp_old' => $p->rrp_old,
                    'short_description' => $this->locale->trans($p, 'short_description'),
                ];
            }
        }

        return array_map(
            fn ($category_title, $items) => [
                'category_title' => $category_title,
                'products'       => $items,
            ],
            array_keys($groups),
            array_values($groups),
        );
    }
}
