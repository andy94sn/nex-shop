<?php

declare(strict_types=1);

namespace Modules\Marketing\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Marketing\Models\Promotion;

class PromotionsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $perPage = $args['perPage'] ?? 12;
        $page    = $args['page'] ?? 1;

        $result = Promotion::whereIn('status', ['active', 'expired'])
            ->latest('starts_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data'         => $result->map(fn (Promotion $p) => [
                'id'           => $p->id,
                'slug'         => $p->slug,
                'title'        => $p->title,
                'banner_image' => $p->banner_image,
                'ends_at'      => $p->ends_at,
                'days_left'    => $p->days_left,
                'status'       => $p->status,
            ])->toArray(),
            'total'        => $result->total(),
            'current_page' => $result->currentPage(),
            'last_page'    => $result->lastPage(),
            'per_page'     => $result->perPage(),
        ];
    }
}
