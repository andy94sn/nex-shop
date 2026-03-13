<?php

declare(strict_types=1);

namespace Modules\Interactions\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Models\Product;

/**
 * Server-side product compare stored in Redis keyed by session ID.
 *
 * Structure: compare:{sessionId} → {
 *   categoryId: [article1, article2, article3]  (max 3 per category)
 * }
 */
class CompareService
{
    private int $maxPerCategory;
    private int $ttl;

    public function __construct()
    {
        $this->maxPerCategory = (int) env('COMPARE_MAX_PER_CATEGORY', 3);
        $this->ttl            = (int) env('CACHE_TTL_CART', 2592000);
    }

    private function key(string $sessionId): string
    {
        return "compare:{$sessionId}";
    }

    public function get(string $sessionId): array
    {
        return Cache::get($this->key($sessionId), []);
    }

    public function add(string $sessionId, string $article): array
    {
        $data    = $this->get($sessionId);
        $product = Product::where('article', $article)->firstOrFail();
        $catId   = (string) $product->category_id;

        $data[$catId] = $data[$catId] ?? [];

        if (in_array($article, $data[$catId], true)) {
            return ['success' => false, 'error' => 'already_added'];
        }

        if (count($data[$catId]) >= $this->maxPerCategory) {
            return ['success' => false, 'error' => 'max_reached'];
        }

        $data[$catId][] = $article;
        Cache::put($this->key($sessionId), $data, $this->ttl);

        return ['success' => true];
    }

    public function remove(string $sessionId, string $article): void
    {
        $data    = $this->get($sessionId);
        $product = Product::where('article', $article)->first();

        if (! $product) return;

        $catId = (string) $product->category_id;

        if (isset($data[$catId])) {
            $data[$catId] = array_values(
                array_filter($data[$catId], fn ($a) => $a !== $article)
            );

            if (empty($data[$catId])) {
                unset($data[$catId]);
            }

            Cache::put($this->key($sessionId), $data, $this->ttl);
        }
    }

    public function clearCategory(string $sessionId, int $categoryId): void
    {
        $data = $this->get($sessionId);
        unset($data[(string) $categoryId]);
        Cache::put($this->key($sessionId), $data, $this->ttl);
    }

    public function getCategoryIds(string $sessionId): array
    {
        return array_keys($this->get($sessionId));
    }
}
