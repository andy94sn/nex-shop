<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CompareService;
use Modules\Catalog\Models\Category;

class CompareCategoriesQuery
{
    public function __construct(private readonly CompareService $compare) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId   = request()->session()->getId();
        $locale      = app()->getLocale();
        $categoryIds = $this->compare->getCategoryIds($sessionId);
        $data        = $this->compare->get($sessionId);

        if (empty($categoryIds)) {
            return [];
        }

        return Category::whereIn('id', $categoryIds)
            ->get()
            ->map(fn (Category $category) => [
                'id'           => $category->id,
                'name'         => $category->getTranslation('name', $locale),
                'slug'         => $category->slug,
                'product_count' => count($data[(string) $category->id] ?? []),
            ])
            ->values()
            ->all();
    }
}
