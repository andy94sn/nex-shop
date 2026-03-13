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

class CategoryPageQuery
{
    use AppliesProductFilters, FormatsCategoryData, FormatsProductCard, ResolvesModelBySlug;

    public function __construct(
        private readonly CartService    $cart,
        private readonly WishlistService $wishlist,
        private readonly LocaleService  $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $locale    = $this->locale->get();

        // slug is a JSON column — match against the current locale value
        $category = $this->findActiveBySlug(Category::class, $args['slug'], $locale);
        abort_if($category->is_hidden_in_store, 404);

        // Include the category itself + every descendant (recursive CTE, single query).
        $categoryIds = $category->descendantIds();

        $query = Product::query()
            ->whereIn('category_id', $categoryIds)
            ->active();

        $this->applyFilters($query, $args['filters'] ?? [], $locale, $categoryIds);

        if (! empty($args['search'])) {
            $query->search($args['search'], $locale);
        }

        $this->applySort($query, $args['sort'] ?? 'DEFAULT');

        $total   = $query->count();
        $perPage = $args['perPage'] ?? 24;
        $page    = $args['page'] ?? 1;

        $products = $query
            ->with(['brand', 'mainImage'])
            ->paginate($perPage, ['*'], 'page', $page);

        $wishlistItems = $this->wishlist->get($sessionId);
        $cartItems     = array_keys($this->cart->get($sessionId));

        return [
            'category'   => $this->formatCategory($category, $locale),
            'products'   => [
                'data'         => $products->map(fn ($p) => $this->formatProductCard($p, $wishlistItems, $cartItems, $locale))->toArray(),
                'total'        => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
            ],
            'filters'    => $this->buildFilters($category->id, $categoryIds, $locale),
            'total'      => $total,
            'breadcrumb' => $this->buildBreadcrumb($category, $locale),
        ];
    }

    // ── Category-specific helpers ─────────────────────────────────────────

    private function buildFilters(int $categoryId, array $categoryIds, string $locale): array
    {
        $base = Product::whereIn('category_id', $categoryIds)->active();

        return [
            'brands' => Brand::whereHas('products', fn ($q) => $q->whereIn('category_id', $categoryIds)->active())
                ->withCount(['products as count' => fn ($q) => $q->whereIn('category_id', $categoryIds)->active()])
                ->get()
                ->map(fn ($b) => [
                    'id'    => $b->id,
                    'title' => $b->title,
                    'count' => $b->count,
                ])
                ->values()->toArray(),

            'price_min'     => (float) ($base->min('rrp') ?? 0),
            'price_max'     => (float) ($base->max('rrp') ?? 0),
            'credit_plans'  => [],
            'attributes'    => [],
            'subcategories' => Category::where('parent_id', $categoryId)
                ->visibleInStore()
                ->get()
                ->map(fn ($c) => $this->formatCategoryBasic($c, $locale))
                ->toArray(),
        ];
    }
}
