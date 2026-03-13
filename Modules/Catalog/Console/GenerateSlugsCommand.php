<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class GenerateSlugsCommand extends Command
{
    protected $signature = 'catalog:generate-slugs
                            {--model= : Run only for a specific model: brands, categories, products}
                            {--force : Overwrite slugs that are already set}';

    protected $description = 'Generate translatable slugs for brands, categories and products based on their title in all available locales.';

    /** Locales the shop supports. */
    private array $locales = ['ro', 'ru'];

    public function handle(): int
    {
        $model = $this->option('model');
        $force = (bool) $this->option('force');

        if (! $model || $model === 'brands') {
            $this->processBrands($force);
        }

        if (! $model || $model === 'categories') {
            $this->processCategories($force);
        }

        if (! $model || $model === 'products') {
            $this->processProducts($force);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    // ── Brands ────────────────────────────────────────────────────────────

    private function processBrands(bool $force): void
    {
        $this->info('Generating slugs for brands…');

        $query = Brand::withTrashed();

        if (! $force) {
            // Only brands that have no slug set for any locale
            $query->where(function ($q) {
                foreach ($this->locales as $locale) {
                    $q->orWhereNull("slug->{$locale}")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.{$locale}')) = ''");
                }
            });
        }

        $count = 0;

        $query->chunkById(200, function ($brands) use ($force, &$count) {
            foreach ($brands as $brand) {
                // Brand title is a plain string, not translatable
                $slugs = [];

                foreach ($this->locales as $locale) {
                    $existing = $brand->getTranslation('slug', $locale, false);

                    if (! $force && ! empty($existing)) {
                        $slugs[$locale] = $existing;
                        continue;
                    }

                    $slugs[$locale] = $this->uniqueSlug(Brand::class, Str::slug($brand->title), $locale, $brand->id);
                }

                $brand->setTranslations('slug', $slugs)->saveQuietly();
                $count++;
            }
        });

        $this->line("  → {$count} brand(s) updated.");
    }

    // ── Categories ────────────────────────────────────────────────────────

    private function processCategories(bool $force): void
    {
        $this->info('Generating slugs for categories…');

        $count = 0;

        Category::withTrashed()->chunkById(200, function ($categories) use ($force, &$count) {
            foreach ($categories as $category) {
                $slugs   = [];
                $changed = false;

                foreach ($this->locales as $locale) {
                    $existing = $category->getTranslation('slug', $locale, false);

                    if (! $force && ! empty($existing)) {
                        $slugs[$locale] = $existing;
                        continue;
                    }

                    $title = $category->getTranslation('title', $locale, false)
                          ?: $category->getTranslation('title', $this->locales[0], false)
                          ?: '';

                    if (empty($title)) {
                        $slugs[$locale] = $existing ?? '';
                        continue;
                    }

                    $slugs[$locale] = $this->uniqueSlug(Category::class, Str::slug($title), $locale, $category->id);
                    $changed        = true;
                }

                if ($changed) {
                    $category->setTranslations('slug', $slugs)->saveQuietly();
                    $count++;
                }
            }
        });

        $this->line("  → {$count} categor(ies) updated.");
    }

    // ── Products ──────────────────────────────────────────────────────────

    private function processProducts(bool $force): void
    {
        $this->info('Generating slugs for products…');

        $count = 0;

        Product::withTrashed()->chunkById(200, function ($products) use ($force, &$count) {
            foreach ($products as $product) {
                $slugs   = [];
                $changed = false;

                foreach ($this->locales as $locale) {
                    $existing = $product->getTranslation('slug', $locale, false);

                    if (! $force && ! empty($existing)) {
                        $slugs[$locale] = $existing;
                        continue;
                    }

                    $title = $product->getTranslation('title', $locale, false)
                          ?: $product->getTranslation('title', $this->locales[0], false)
                          ?: '';

                    if (empty($title)) {
                        $slugs[$locale] = $existing ?? '';
                        continue;
                    }

                    $slugs[$locale] = $this->uniqueSlug(Product::class, Str::slug($title), $locale, $product->id);
                    $changed        = true;
                }

                if ($changed) {
                    $product->setTranslations('slug', $slugs)->saveQuietly();
                    $count++;
                }
            }
        });

        $this->line("  → {$count} product(s) updated.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Ensure the generated slug is unique for the given locale within the model table.
     * Appends -1, -2, … until a free slot is found.
     */
    private function uniqueSlug(string $modelClass, string $base, string $locale, int $excludeId): string
    {
        $slug      = $base;
        $suffix    = 1;
        /** @var \Illuminate\Database\Eloquent\Model $modelClass */
        $table     = (new $modelClass)->getTable();

        while (
            DB::table($table)
               ->where('id', '!=', $excludeId)
               ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.{$locale}')) = ?", [$slug])
               ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
