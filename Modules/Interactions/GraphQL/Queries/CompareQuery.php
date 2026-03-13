<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Interactions\Services\CompareService;
use Modules\Catalog\Models\Product;

class CompareQuery
{
    public function __construct(private readonly CompareService $compare) {}

    /**
     * Returns products grouped by category for a specific category.
     * Args: category_id (Int!)
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId  = request()->session()->getId();
        $locale     = app()->getLocale();
        $data       = $this->compare->get($sessionId);
        $catId      = (string) $args['category_id'];
        $articles   = $data[$catId] ?? [];

        if (empty($articles)) {
            return [];
        }

        return Product::whereIn('article', $articles)
            ->with([
                'images'          => fn ($q) => $q->orderBy('position')->limit(1),
                'attributeValues' => fn ($q) => $q->with('attribute.group'),
            ])
            ->get()
            ->map(fn (Product $product) => [
                'id'                  => $product->id,
                'article'             => $product->article,
                'slug'                => $product->slug,
                'title'               => $product->getTranslation('title', $locale),
                'price'               => $product->price,
                'price_old'          => $product->price_old,
                'discount_percentage' => $product->discount_percentage,
                'thumbnail'           => $product->images->first()?->getFirstMediaUrl('product-images'),
                'attributes'          => $product->attributeValues->map(fn ($av) => [
                    'group_name' => optional($av->attribute->group)->getTranslation('name', $locale),
                    'name'       => $av->attribute->getTranslation('name', $locale),
                    'value'      => $av->getTranslation('value', $locale),
                ])->all(),
            ])
            ->values()
            ->all();
    }
}
