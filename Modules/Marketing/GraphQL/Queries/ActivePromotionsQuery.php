<?php

declare(strict_types=1);

namespace Modules\Marketing\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Marketing\Models\Promotion;

class ActivePromotionsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $limit = $args['limit'] ?? 4;

        return Promotion::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (Promotion $p) => [
                'id'                   => $p->id,
                'slug'                 => $p->slug,
                'title'                => $p->title,
                'banner_image'         => $p->banner_image,
                'ends_at'              => $p->ends_at,
                'days_left'            => $p->days_left,
                'status'               => $p->status,
            ])
            ->toArray();
    }
}
