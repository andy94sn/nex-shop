<?php

declare(strict_types=1);

namespace Modules\Catalog\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\GraphQL\Concerns\ResolvesModelBySlug;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;

class ProductQuery
{
    use ResolvesModelBySlug;
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): ?array
    {
        $locale = $this->locale->get();

        $product = $this->findActiveBySlugOrNull(Product::class, $args['slug'], $locale);

        if (! $product) return null;

        $product->load([
            'brand',
            'category',
            'images',
            'attachments',
            'variants',
            'descriptionSections',
            'attributeValues.attributeValue.attribute.group',
            'creditPlans' => fn($q) => $q->active()->ordered(),
        ]);

        // Group attribute values by their group.
        // Path: ProductAttributeValue → attributeValue (AttributeValue) → attribute (Attribute) → group (AttributeGroup)
        $attributeGroups = $product->attributeValues
            ->filter(fn($pav) => $pav->attributeValue?->attribute?->group !== null)
            ->groupBy(fn($pav) => $pav->attributeValue->attribute->group->id)
            ->map(fn($values) => [
                'id'         => $values->first()->attributeValue->attribute->group->id,
                'title'      => $this->locale->trans($values->first()->attributeValue->attribute->group, 'title'),
                'image'      => $values->first()->attributeValue->attribute->group->image,
                'attributes' => $values->map(fn($pav) => [
                    'id'    => $pav->attributeValue->attribute->id,
                    'title' => $this->locale->trans($pav->attributeValue->attribute, 'title'),
                    'value' => $this->locale->trans($pav->attributeValue, 'value'),
                ])->toArray(),
            ])
            ->values()
            ->toArray();

        // Credit plans with calculated rates
        $creditPlans = $product->creditPlans->map(fn($plan) => [
            'id'               => $plan->id,
            'months'           => $plan->months,
            'interest_label'   => $plan->interest_label,
            'monthly_rate'     => $plan->monthlyRate($product->rrp),
            'total'            => round($plan->monthlyRate($product->rrp) * $plan->months, 2),
            'is_zero_interest' => $plan->is_zero_interest,
        ])->toArray();

        $creditMinRate = ! empty($creditPlans)
            ? min(array_column($creditPlans, 'monthly_rate'))
            : null;

        return [
            'id'                   => $product->id,
            'slug'                 => $this->locale->trans($product, 'slug'),
            'title'                => $this->locale->trans($product, 'title'),
            'subtitle'             => $this->locale->trans($product, 'subtitle'),
            'code'                 => $product->code,
            'article'              => $product->article,
            'status'               => $product->stock > 0 ? 'In stock' : 'Out of stock',
            'is_new'               => $product->is_new,
            'is_new_until'         => $product->is_new_until,
            'image'                => $product->images->firstWhere('is_main', true)?->path,
            'gallery'              => $product->images->toArray(),
            'rrp'                  => $product->rrp,
            'rrp_old'              => $product->rrp_old,
            'price_eur'            => $product->price_eur,
            'stock'                => $product->stock,
            'discount_percentage'  => $product->discount_percentage,
            'credit_min_rate'      => $creditMinRate,
            'brand'                => $product->brand ? ['id' => $product->brand->id, 'title' => $product->brand->title] : null,
            'category'             => $product->category ? [
                'id'    => $product->category->id,
                'slug'  => $this->locale->trans($product->category, 'slug'),
                'title' => $this->locale->trans($product->category, 'title'),
            ] : null,
            'breadcrumb'           => $this->buildBreadcrumb($product->category, $locale),
            'variants'             => $product->variants->toArray(),
            'short_description'    => $this->locale->trans($product, 'short_description'),
            'description_sections' => $product->descriptionSections->map(fn($s) => [
                'id'      => $s->id,
                'title'   => $this->locale->trans($s, 'title'),
                'content' => $this->locale->trans($s, 'content'),
                'image'   => $s->image,
                'sort'    => $s->sort,
            ])->toArray(),
            'attribute_groups'     => $attributeGroups,
            'youtube_url'          => $product->youtube_url,
            'attachments'          => $product->attachments->map(fn($a) => [
                'id'              => $a->id,
                'title'           => $this->locale->trans($a, 'title'),
                'filename'        => $a->filename,
                'path'            => $a->path,
                'file_type'       => $a->file_type,
                'file_size'       => $a->file_size,
                'is_presentation' => $a->is_presentation,
            ])->toArray(),
            'meta_title'           => $this->locale->trans($product, 'meta_title'),
            'meta_description'     => $this->locale->trans($product, 'meta_description'),
            'credit_plans'         => $creditPlans,
        ];
    }

    private function buildBreadcrumb(?Category $category, string $locale): array
    {
        if (! $category) return [];

        $breadcrumb = [[
            'id'    => $category->id,
            'slug'  => $this->locale->trans($category, 'slug'),
            'title' => $this->locale->trans($category, 'title'),
        ]];

        $parent = $category->parent;
        while ($parent) {
            array_unshift($breadcrumb, [
                'id'    => $parent->id,
                'slug'  => $this->locale->trans($parent, 'slug'),
                'title' => $this->locale->trans($parent, 'title'),
            ]);
            $parent = $parent->parent;
        }

        return $breadcrumb;
    }
}
