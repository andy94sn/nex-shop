<?php

declare(strict_types=1);

namespace Modules\Marketing\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\Models\Product;

class SimilarProductsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $limit   = $args['limit'] ?? 4;
        $product = Product::where('slug', $args['product_slug'])->with('category')->firstOrFail();
        $category = $product->category;

        $similarCategoryIds = $category?->similar_categories;

        if (! empty($similarCategoryIds)) {
            $query = Product::whereIn('category_id', $similarCategoryIds);
        } else {
            $query = Product::where('category_id', $product->category_id);
        }

        return $query
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['brand', 'images' => fn ($q) => $q->where('is_main', true)])
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(fn (Product $p) => [
                'id'                  => $p->id,
                'slug'                => $p->slug,
                'title'               => $p->title,
                'subtitle'            => $p->subtitle,
                'article'             => $p->article,
                'code'                => $p->code,
                'image'               => $p->images->first()?->path,
                'rrp'                 => $p->rrp,
                'rrp_old'             => $p->rrp_old,
                'stock'               => $p->stock,
                'is_new'              => $p->is_new,
                'discount_percentage' => $p->discount_percentage,
                'best_credit_label'   => null,
                'is_in_wishlist'      => false,
                'is_in_cart'          => false,
                'brand'               => $p->brand ? ['id' => $p->brand->id, 'title' => $p->brand->title] : null,
            ])
            ->toArray();
    }
}
