<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CompareService;

class RemoveFromCompareMutation
{
    public function __construct(private readonly CompareService $compare) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $this->compare->remove($sessionId, $args['article']);

        return ['success' => true, 'error' => null];
    }
}
