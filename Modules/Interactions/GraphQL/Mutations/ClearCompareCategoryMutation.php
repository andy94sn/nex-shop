<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use App\Models\Concerns\HasHashId;
use Modules\Interactions\Services\CompareService;

class ClearCompareCategoryMutation
{
    public function __construct(private readonly CompareService $compare) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): bool
    {
        $categoryId = HasHashId::decodeHashId($args['category_id']);
        $this->compare->clearCategory(request()->session()->getId(), $categoryId);

        return true;
    }
}
