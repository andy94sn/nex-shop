<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use App\Models\Concerns\HasHashId;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CompareService;
use Modules\Catalog\Models\Category;
use Modules\Core\Services\LocaleService;

class CompareCategoriesQuery
{
    public function __construct(
        private readonly CompareService $compare,
        private readonly LocaleService  $locale
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId   = request()->session()->getId();
        // $locale      = app()->getLocale();
        $categoryIds = $this->compare->getCategoryIds($sessionId);
        $data        = $this->compare->get($sessionId);

        if (empty($categoryIds)) {
            return [];
        }

        return Category::whereIn('id', $categoryIds)
            ->get()
            ->map(fn (Category $category) => [
                'id'           => HasHashId::hashId($category->id),
                'title'        => $this->locale->trans($category,'title'),
                'slug'         => $category->slug,
                'product_count' => count($data[(string) $category->id] ?? []),
            ])
            ->values()
            ->all();
    }
}
