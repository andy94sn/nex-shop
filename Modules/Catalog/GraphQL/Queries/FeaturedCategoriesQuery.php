<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\Models\Category;
use Modules\Core\Services\LocaleService;

class FeaturedCategoriesQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        // $locale = $this->locale->get();

        return Category::featured()
            ->visibleInStore()
            ->get()
            ->map(fn (Category $c) => [
                'id'             => $c->id,
                'slug'           => $this->locale->trans($c, 'slug'),
                'title'          => $this->locale->trans($c, 'title'),
                'featured_image' => $c->featured_image,
                'products_count' => $c->activeProducts()->count(),
            ])
            ->toArray();
    }
}

