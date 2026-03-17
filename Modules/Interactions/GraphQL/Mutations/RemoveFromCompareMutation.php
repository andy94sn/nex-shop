<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use App\Models\Concerns\HasHashId;
use Modules\Catalog\Models\Product;
use Modules\Interactions\Services\CompareService;

class RemoveFromCompareMutation
{
    public function __construct(private readonly CompareService $compare) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): bool
    {
        $productId = HasHashId::decodeHashId($args['id']);
        $product   = Product::findOrFail($productId);

        $sessionId = request()->session()->getId();
        $this->compare->remove($sessionId, $product->article);

        return true;
    }
}
