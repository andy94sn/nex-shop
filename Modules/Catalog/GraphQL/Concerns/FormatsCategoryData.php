<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Concerns;

use App\Models\Concerns\HasHashId;
use Modules\Catalog\Models\Category;

/**
 * Category formatting helpers used by CategoryPageQuery (and any future
 * query that needs to render categories or breadcrumbs).
 *
 * Requires the consuming class to have a `$this->locale` (LocaleService).
 */
trait FormatsCategoryData
{
    private function formatCategory(Category $category, string $locale): array
    {
        return [
            'id'               => HasHashId::hashId($category->id),
            'slug'             => $this->locale->trans($category, 'slug'),
            'title'            => $this->locale->trans($category, 'title'),
            'description'      => $this->locale->trans($category, 'description'),
            'image'            => $category->image,
            'meta_title'       => $this->locale->trans($category, 'meta_title'),
            'meta_description' => $this->locale->trans($category, 'meta_description'),
            'subcategories'    => $category->children()
                ->visibleInStore()
                ->get()
                ->map(fn ($c) => $this->formatCategoryBasic($c, $locale))
                ->toArray(),
        ];
    }

    private function formatCategoryBasic(Category $c, string $locale): array
    {
        return [
            'id'    => HasHashId::hashId($c->id),
            'slug'  => $this->locale->trans($c, 'slug'),
            'title' => $this->locale->trans($c, 'title'),
        ];
    }

    private function buildBreadcrumb(Category $category, string $locale): array
    {
        $crumb = [[
            'id'    => HasHashId::hashId($category->id),
            'slug'  => $this->locale->trans($category, 'slug'),
            'title' => $this->locale->trans($category, 'title'),
        ]];

        $parent = $category->parent;
        while ($parent) {
            array_unshift($crumb, [
                'id'    => HasHashId::hashId($parent->id),
                'slug'  => $this->locale->trans($parent, 'slug'),
                'title' => $this->locale->trans($parent, 'title'),
            ]);
            $parent = $parent->parent;
        }

        return $crumb;
    }
}
