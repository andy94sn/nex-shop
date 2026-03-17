<?php

declare(strict_types=1);

namespace Modules\Interactions\Services;

use GraphQL\Error\UserError;
use Illuminate\Support\Facades\Cache;
use App\Models\Concerns\HasHashId;
use Modules\Catalog\Models\Product;
use Modules\Settings\Models\SiteSettings;

/**
 * Server-side cart stored in Redis keyed by session ID.
 *
 * Structure: cart:{sessionId} → [
 *   {product_id} => [
 *     'product_id' => int,
 *     'article'    => string,
 *     'quantity'   => int,
 *     'price'      => float,   // snapshot at add time
 *     'title'      => array,   // snapshot (translatable)
 *     'image'      => string,  // snapshot
 *   ]
 * ]
 */
class CartService
{
    private int $ttl;

    public function __construct()
    {
        $this->ttl = (int) env('CACHE_TTL_CART', 2592000);
    }

    private function key(string $sessionId): string
    {
        return "cart:{$sessionId}";
    }

    /** When true, stock validation is skipped in both cart and checkout. */
    public function allowBackorder(): bool
    {
        return (bool) SiteSettings::instance()->allow_backorder;
    }

    public function get(string $sessionId): array
    {
        return Cache::get($this->key($sessionId), []);
    }

    public function add(string $sessionId, string $hashedId, int $quantity = 1): void
    {
        $productId = HasHashId::decodeHashId($hashedId);
        $items     = $this->get($sessionId);

        // Use the real DB id as the cart key for consistency.
        $key = (string) $productId;

        if (isset($items[$key])) {
            $newQty  = $items[$key]['quantity'] + $quantity;
            $product = Product::active()->find($productId);

            if (!$this->allowBackorder() && $product?->hasStockConflict($newQty)) {
                // $available = $product->quantity ?? 0;
                throw new UserError("Insufficient items in stock");
            }

            $items[$key]['quantity'] = $newQty;
        } else {
            $product = Product::active()->find($productId);
            if (!$product) {
                throw new UserError("Product not found or inactive.");
            }

            if (!$this->allowBackorder() && $product->hasStockConflict($quantity)) {
                // $available = $product->quantity ?? 0;
                throw new UserError("Insufficient items in stock");
            }

            $items[$key] = [
                'product_id' => $productId,
                'article'    => $product->article,
                'code'       => $product->code,
                'quantity'   => $quantity,
                'price'      => $product->rrp,
                'title'      => $product->getTranslations('title'),
                'subtitle'   => $product->getTranslations('subtitle'),
                'image'      => optional($product->images()->where('is_main', true)->first())->path,
            ];
        }

        Cache::put($this->key($sessionId), $items, $this->ttl);
    }

    public function remove(string $sessionId, string $hashedId): void
    {
        $productId = HasHashId::decodeHashId($hashedId);
        $items     = $this->get($sessionId);
        unset($items[(string) $productId]);
        Cache::put($this->key($sessionId), $items, $this->ttl);
    }

    public function updateQuantity(string $sessionId, string $hashedId, int $quantity): void
    {
        $productId = HasHashId::decodeHashId($hashedId);
        $key       = (string) $productId;
        $items     = $this->get($sessionId);

        if (isset($items[$key])) {
            if ($quantity <= 0) {
                $this->remove($sessionId, $hashedId);
                return;
            }

            if (!$this->allowBackorder()) {
                $product = Product::active()->find($productId);
                if ($product?->hasStockConflict($quantity)) {
                    $available = $product->quantity ?? 0;
                    throw new UserError("Insufficient items in stock");
                }
            }

            $items[$key]['quantity'] = $quantity;
            Cache::put($this->key($sessionId), $items, $this->ttl);
        }
    }

    public function clear(string $sessionId): void
    {
        Cache::forget($this->key($sessionId));
    }

    public function count(string $sessionId): int
    {
        return array_sum(array_column($this->get($sessionId), 'quantity'));
    }

    public function has(string $sessionId, string $hashedId): bool
    {
        $productId = HasHashId::decodeHashId($hashedId);
        return isset($this->get($sessionId)[(string) $productId]);
    }
}
