<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\AppliesProductFilters;
use Modules\Catalog\GraphQL\Concerns\FormatsProductCard;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;
use Modules\Interactions\Services\WishlistService;
use Modules\Interactions\Services\CartService;

/**
 * Full search with filters and pagination (Task 16b).
 * Delegates to Scout for ranking then applies Eloquent filters.
 */
class SearchQuery
{
    use AppliesProductFilters, FormatsProductCard;

    public function __construct(
        private readonly WishlistService $wishlist,
        private readonly CartService     $cart,
        private readonly LocaleService   $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $queryStr  = trim($args['query']);
        $locale    = $this->locale->get();
        $perPage   = (int) ($args['perPage'] ?? 24);
        $page      = (int) ($args['page'] ?? 1);
        $filters   = $args['filters'] ?? [];
        $sessionId = request()->session()->getId();

        if (strlen($queryStr) < 2) {
            return $this->emptyResponse($page, $perPage);
        }

        // Scout search — get IDs ranked by relevance
        $scoutIds = Product::search($queryStr)->take(500)->keys()->all();

        if (empty($scoutIds)) {
            return $this->emptyResponse($page, $perPage);
        }

        // Build Eloquent query preserving Scout ordering
        $query = Product::whereIn('id', $scoutIds)
            ->where('is_active', true)
            ->with(['brand', 'images' => fn ($q) => $q->where('is_main', true)]);

        $this->applyFilters($query, $filters, $locale);
        $query->orderByRaw('FIELD(id, ' . implode(',', $scoutIds) . ')');

        $paginator     = $query->paginate($perPage, ['*'], 'page', $page);
        $wishlistItems = $this->wishlist->get($sessionId);
        $cartItems     = array_keys($this->cart->get($sessionId));

        $data = $paginator->getCollection()
            ->map(fn (Product $p) => $this->formatProductCard($p, $wishlistItems, $cartItems, $locale))
            ->values()
            ->all();

        return [
            'products' => [
                'data'         => $data,
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
            ],
            'filters'         => $this->buildSearchFilters($scoutIds),
            'total'           => $paginator->total(),
            'category_counts' => $this->buildCategoryCounts($scoutIds, $locale),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function emptyResponse(int $page, int $perPage): array
    {
        return [
            'products' => [
                'data'         => [],
                'total'        => 0,
                'current_page' => $page,
                'last_page'    => 1,
                'per_page'     => $perPage,
            ],
            'filters' => [
                'brands'        => [],
                'price_min'     => 0.0,
                'price_max'     => 0.0,
                'credit_plans'  => [],
                'attributes'    => [],
                'subcategories' => [],
            ],
            'total'           => 0,
            'category_counts' => [],
        ];
    }

    /** Brand options + price range, derived from the full unfiltered Scout set. */
    private function buildSearchFilters(array $scoutIds): array
    {
        $base = Product::whereIn('id', $scoutIds)->where('is_active', true);

        $brands = (clone $base)
            ->with('brand')
            ->get()
            ->groupBy('brand_id')
            ->map(fn ($grp) => [
                'id'    => $grp->first()->brand?->id,
                'title' => $grp->first()->brand?->title,
                'count' => $grp->count(),
            ])
            ->filter(fn ($b) => $b['id'])
            ->values()
            ->all();

        return [
            'brands'        => $brands,
            'price_min'     => (float) ($base->min('rrp') ?? 0),
            'price_max'     => (float) ($base->max('rrp') ?? 0),
            'credit_plans'  => [],
            'attributes'    => [],
            'subcategories' => [],
        ];
    }

    /** Per-category hit counts for the category-tab strip on the search page. */
    private function buildCategoryCounts(array $scoutIds, string $locale): array
    {
        return Product::whereIn('id', $scoutIds)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($grp) => [
                'category_id'    => (string) $grp->first()->category_id,
                'category_title' => $grp->first()->category
                    ? $this->locale->trans($grp->first()->category, 'title')
                    : '',
                'count'          => $grp->count(),
            ])
            ->filter(fn ($c) => $c['category_id'])
            ->values()
            ->all();
    }
}
