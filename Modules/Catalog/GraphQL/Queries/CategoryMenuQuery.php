<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;

class CategoryMenuQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $locale = $this->locale->get();

        $categories = Category::with([
            'children' => fn ($q) => $q->visibleInMenu()->orderedBySort(),
        ])
            ->rootLevel()
            ->visibleInMenu()
            ->orderedBySort()
            ->get();

        // Collect every category ID we need (roots + their children).
        $allIds = $categories->flatMap(
            fn (Category $c) => $c->children->pluck('id')->prepend($c->id)
        )->unique()->values()->all();

        // One query: count active products grouped by category_id.
        // This mirrors the exact same active() scope used in CategoryPageQuery.
        $rawCounts = Product::active()
            ->whereIn('category_id', $allIds)
            ->select('category_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id')
            ->all();

        // For each category the visible count = own products + all children products.
        $countFor = function (Category $c) use ($rawCounts): int {
            $ids = $c->children->pluck('id')->prepend($c->id)->all();
            return array_sum(array_intersect_key($rawCounts, array_flip($ids)));
        };

        return $categories->map(fn (Category $c) => $this->formatCategory($c, $locale, $countFor, $rawCounts))->toArray();
    }

    private function formatCategory(Category $c, string $locale, callable $countFor, array $rawCounts): array
    {
        return [
            'id'             => $c->id,
            'slug'           => $c->getTranslation('slug', $locale, false),
            'title'          => $c->getTranslation('title', $locale, false),
            'image'          => $c->image,
            'products_count' => $countFor($c),
            'children'       => $c->children->map(fn (Category $child) => [
                'id'             => $child->id,
                'slug'           => $child->getTranslation('slug', $locale, false),
                'title'          => $child->getTranslation('title', $locale, false),
                'image'          => $child->image,
                'products_count' => (int) ($rawCounts[$child->id] ?? 0),
            ])->toArray(),
        ];
    }
}

