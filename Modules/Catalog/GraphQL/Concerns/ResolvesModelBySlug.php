<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Provides a reusable helper to look up any model by its
 * Spatie-translatable JSON slug column for the current locale.
 *
 * Usage:
 *   $this->findBySlug(Category::class, $slug, $locale);
 *   $this->findBySlug(Brand::class,    $slug, $locale);
 *   $this->findBySlug(Product::class,  $slug, $locale);
 */
trait ResolvesModelBySlug
{
    /**
     * Return the first model whose translated slug matches $slug in $locale,
     * or throw a ModelNotFoundException (→ 404) if none is found.
     *
     * @template T of Model
     * @param  class-string<T>  $modelClass
     * @return T
     */
    private function findBySlug(string $modelClass, string $slug, string $locale): Model
    {
        return $modelClass::where("slug->{$locale}", $slug)->firstOrFail();
    }

    /**
     * Same as findBySlug() but also requires is_active = true.
     *
     * @template T of Model
     * @param  class-string<T>  $modelClass
     * @return T
     */
    private function findActiveBySlug(string $modelClass, string $slug, string $locale): Model
    {
        return $modelClass::where("slug->{$locale}", $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Same as findActiveBySlug() but returns null instead of throwing.
     *
     * @template T of Model
     * @param  class-string<T>  $modelClass
     * @return T|null
     */
    private function findActiveBySlugOrNull(string $modelClass, string $slug, string $locale): ?Model
    {
        return $modelClass::where("slug->{$locale}", $slug)
            ->where('is_active', true)
            ->first();
    }
}
