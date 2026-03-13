<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\AppliesProductFilters;
use Modules\Catalog\GraphQL\Concerns\FormatsCategoryData;
use Modules\Catalog\GraphQL\Concerns\FormatsProductCard;
use Modules\Catalog\GraphQL\Concerns\ResolvesModelBySlug;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;
use Modules\Interactions\Services\CartService;
use Modules\Interactions\Services\WishlistService;

class BrandPageQuery
{
    use AppliesProductFilters, FormatsCategoryData, FormatsProductCard, ResolvesModelBySlug;

    public function __construct(
        private readonly CartService     $cart,
        private readonly WishlistService $wishlist,
        private readonly LocaleService   $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $locale    = $this->locale->get();

        $brand = $this->findActiveBySlug(Brand::class, $args['slug'], $locale);

        $query = Product::where('brand_id', $brand->id)->active();

        $perPage = $args['perPage'] ?? 24;
        $page    = $args['page'] ?? 1;
        $sort    = $args['sort'] ?? 'DEFAULT';

        $this->applySort($query, $sort);

        $paginated = $query->with(['brand', 'mainImage'])
            ->paginate($perPage, ['*'], 'page', $page);

        $wishlistItems = $this->wishlist->get($sessionId);
        $cartItems     = array_keys($this->cart->get($sessionId));

        // Categories that have products of this brand
        $categoryIds = $brand->activeProducts()
            ->pluck('category_id')
            ->unique();

        $categoryFilter = Category::whereIn('id', $categoryIds)
            ->active()
            ->get()
            ->map(fn (Category $c) => [
                'id'    => $c->id,
                'slug'  => $this->locale->trans($c, 'slug'),
                'title' => $this->locale->trans($c, 'title'),
            ])
            ->toArray();

        return [
            'brand' => [
                'id'               => $brand->id,
                'slug'             => $this->locale->trans($brand, 'slug'),
                'title'            => $brand->title,
                'description'      => $brand->description,
                'logo'             => $brand->logo,
                'image'            => $brand->image,
                'meta_title'       => $this->locale->trans($brand, 'meta_title'),
                'meta_description' => $this->locale->trans($brand, 'meta_description'),
                'products_count'   => $brand->activeProducts()->count(),
            ],
            'products' => [
                'data'         => $paginated->map(fn ($p) => $this->formatProductCard($p, $wishlistItems, $cartItems, $locale))->toArray(),
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ],
            'filters' => [
                'brands'        => [],
                'price_min'     => (float) ($brand->activeProducts()->min('rrp') ?? 0),
                'price_max'     => (float) ($brand->activeProducts()->max('rrp') ?? 0),
                'credit_plans'  => [],
                'attributes'    => [],
                'subcategories' => $categoryFilter,
            ],
        ];
    }
}
