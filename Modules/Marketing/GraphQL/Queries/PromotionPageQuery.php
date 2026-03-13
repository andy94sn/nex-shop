<?php

declare(strict_types=1);

namespace Modules\Marketing\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Error\UserError;
use Modules\Marketing\Models\Promotion;
use Modules\Interactions\Services\WishlistService;
use Modules\Interactions\Services\CartService;
use Illuminate\Support\Facades\DB;

/**
 * Single promotion page: promotion details + paginated product grid
 * with optional filters (Task 22a).
 */
class PromotionPageQuery
{
    public function __construct(
        private readonly WishlistService $wishlist,
        private readonly CartService $cart,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $locale    = app()->getLocale();
        $sessionId = request()->session()->getId();
        $perPage   = (int) ($args['perPage'] ?? 24);
        $page      = (int) ($args['page'] ?? 1);
        $filters   = $args['filters'] ?? [];

        $promotion = Promotion::where('slug', $args['slug'])
            ->where('status', 'active')
            ->first();

        if (! $promotion) {
            throw new UserError('Promoția nu a fost găsită.');
        }

        // --- Build products query ---
        $query = $promotion->products()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)]);

        if (! empty($filters['brand_ids'])) {
            $query->whereIn('brand_id', $filters['brand_ids']);
        }
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Sort
        $sort = $args['sort'] ?? 'NEWEST';
        match ($sort) {
            'PRICE_ASC'  => $query->orderBy('price'),
            'PRICE_DESC' => $query->orderByDesc('price'),
            'NAME_ASC'   => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}'))"),
            'DISCOUNT'   => $query->orderByDesc(DB::raw('(price_old - price)')),
            default      => $query->orderByPivot('sort'), // promotion-defined order
        };

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $wishlistArticles = array_flip($this->wishlist->get($sessionId));
        $cartArticles     = array_flip(array_column($this->cart->get($sessionId), 'article'));

        // Price range for filters panel
        $priceRange = $promotion->products()
            ->where('is_active', true)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $products = $paginator->getCollection()->map(fn ($product) => [
            'id'                  => $product->id,
            'article'             => $product->article,
            'slug'                => $product->slug,
            'title'               => $product->getTranslation('title', $locale),
            'price'               => $product->price,
            'price_old'          => $product->price_old,
            'discount_percentage' => $product->discount_percentage,
            'is_new'              => $product->is_new,
            'thumbnail'           => $product->images->first()?->getFirstMediaUrl('product-images'),
            'is_in_wishlist'      => isset($wishlistArticles[$product->article]),
            'is_in_cart'          => isset($cartArticles[$product->article]),
        ])->values()->all();

        return [
            'promotion' => [
                'id'                  => $promotion->id,
                'slug'                => $promotion->slug,
                'title'               => $promotion->getTranslation('title', $locale),
                'banner_image'        => $promotion->getTranslation('banner_image', $locale),
                'banner_image_mobile' => $promotion->getTranslation('banner_image_mobile', $locale),
                'ends_at'             => $promotion->ends_at,
                'days_left'           => $promotion->days_left,
                'meta_title'          => $promotion->getTranslation('meta_title', $locale),
                'meta_description'    => $promotion->getTranslation('meta_description', $locale),
            ],
            'products' => [
                'data'         => $products,
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
            ],
            'filters' => [
                'price_min' => $priceRange?->min_price ?? 0,
                'price_max' => $priceRange?->max_price ?? 0,
                'brands'    => [],
                'attributes' => [],
            ],
        ];
    }
}
