<?php

declare(strict_types=1);

namespace Modules\Settings\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;

class SessionTokenQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): string
    {
        return request()->session()->getId();
    }
}
