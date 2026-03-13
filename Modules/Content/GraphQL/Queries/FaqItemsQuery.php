<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\FaqItem;

class FaqItemsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        return FaqItem::where('is_active', true)->orderBy('sort')->get()->toArray();
    }
}
