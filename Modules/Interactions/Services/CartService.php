<?php

declare(strict_types=1);

namespace Modules\Interactions\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Models\Product;

/**
 * Server-side cart stored in Redis keyed by session ID.
 *
 * Structure: cart:{sessionId} → [
 *   article => [
 *     'article'   => string,
 *     'quantity'  => int,
 *     'price'     => float,   // snapshot at add time
 *     'title'     => array,   // snapshot (translatable)
 *     'image'     => string,  // snapshot
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

    public function get(string $sessionId): array
    {
        return Cache::get($this->key($sessionId), []);
    }

    public function add(string $sessionId, string $article, int $quantity = 1): void
    {
        $items = $this->get($sessionId);

        if (isset($items[$article])) {
            $items[$article]['quantity'] += $quantity;
        } else {
            $product = Product::where('article', $article)->firstOrFail();
            $items[$article] = [
                'article'  => $article,
                'quantity' => $quantity,
                'price'    => $product->rrp,
                'title'    => $product->getTranslations('title'),
                'subtitle' => $product->getTranslations('subtitle'),
                'image'    => optional($product->images()->where('is_main', true)->first())->path,
            ];
        }

        Cache::put($this->key($sessionId), $items, $this->ttl);
    }

    public function remove(string $sessionId, string $article): void
    {
        $items = $this->get($sessionId);
        unset($items[$article]);
        Cache::put($this->key($sessionId), $items, $this->ttl);
    }

    public function updateQuantity(string $sessionId, string $article, int $quantity): void
    {
        $items = $this->get($sessionId);

        if (isset($items[$article])) {
            if ($quantity <= 0) {
                $this->remove($sessionId, $article);
                return;
            }
            $items[$article]['quantity'] = $quantity;
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

    public function has(string $sessionId, string $article): bool
    {
        return isset($this->get($sessionId)[$article]);
    }
}
