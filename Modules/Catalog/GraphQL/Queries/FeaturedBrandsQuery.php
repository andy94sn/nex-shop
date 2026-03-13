<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\FormatsBrandData;
use Modules\Catalog\Models\Brand;
use Modules\Core\Services\LocaleService;

class FeaturedBrandsQuery
{
    use FormatsBrandData;

    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $locale = $this->locale->get();
        $limit  = $args['limit'] ?? 8;

        return Brand::active()
            ->featured()
            ->limit($limit)
            ->get()
            ->map(fn (Brand $b) => $this->formatBrand($b, $locale))
            ->toArray();
    }
}

