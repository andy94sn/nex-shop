<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\FormatsBrandData;
use Modules\Catalog\Models\Brand;
use Modules\Core\Services\LocaleService;

class BrandsQuery
{
    use FormatsBrandData;

    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $locale  = $this->locale->get();
        $perPage = $args['perPage'] ?? 24;
        $page    = $args['page'] ?? 1;

        $result = Brand::active()
            ->orderedByTitle()
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $result->map(fn (Brand $b) => $this->formatBrand($b, $locale))->toArray(),
            'total'        => $result->total(),
            'current_page' => $result->currentPage(),
            'last_page'    => $result->lastPage(),
            'per_page'     => $result->perPage(),
        ];
    }
}

