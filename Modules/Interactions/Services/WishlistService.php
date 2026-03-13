<?php

declare(strict_types=1);

namespace Modules\Interactions\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Server-side wishlist stored in Redis keyed by session ID.
 * TTL is configurable via CACHE_TTL_WISHLIST env (default 30 days).
 */
class WishlistService
{
    private int $ttl;

    public function __construct()
    {
        $this->ttl = (int) env('CACHE_TTL_WISHLIST', 2592000);
    }

    private function key(string $sessionId): string
    {
        return "wishlist:{$sessionId}";
    }

    public function get(string $sessionId): array
    {
        return Cache::get($this->key($sessionId), []);
    }

    public function add(string $sessionId, string $article): void
    {
        $items = $this->get($sessionId);

        if (! in_array($article, $items, true)) {
            $items[] = $article;
            Cache::put($this->key($sessionId), $items, $this->ttl);
        }
    }

    public function remove(string $sessionId, string $article): void
    {
        $items = array_values(
            array_filter($this->get($sessionId), fn ($a) => $a !== $article)
        );

        Cache::put($this->key($sessionId), $items, $this->ttl);
    }

    public function clear(string $sessionId): void
    {
        Cache::forget($this->key($sessionId));
    }

    public function count(string $sessionId): int
    {
        return count($this->get($sessionId));
    }

    public function has(string $sessionId, string $article): bool
    {
        return in_array($article, $this->get($sessionId), true);
    }
}
