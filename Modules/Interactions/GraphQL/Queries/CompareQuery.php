<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Queries;

use App\Models\Concerns\HasHashId;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Catalog\Models\Product;
use Modules\Core\Services\LocaleService;
use Modules\Interactions\Services\CompareService;

class CompareQuery
{
    public function __construct(
        private readonly CompareService $compare,
        private readonly LocaleService  $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $data      = $this->compare->get($sessionId);
        $filter    = $args['filter'] ?? 'ALL';

        // category_id from the client is a hashed ID — decode to the raw DB int
        // which is what CompareService uses as its Redis key.
        $rawCategoryId = (string) HasHashId::decodeHashId($args['category_id']);
        $articles      = $data[$rawCategoryId] ?? [];

        $empty = ['products' => []];

        if (empty($articles)) {
            return $empty;
        }

        $products = Product::whereIn('article', $articles)
            ->with([
                'mainImage',
                'brand',
                'attributeValues.attributeValue.attribute.group',
            ])
            ->get();

        if ($products->isEmpty()) {
            return $empty;
        }

        // ── Pre-compute all attribute values across products ──────────────
        // groupTitle → attrTitle → [ productIndex => translatedValue ]
        $groupMap  = [];
        $groupIds  = [];   // groupTitle → hashed group id
        $attrIds   = [];   // groupTitle → attrTitle → hashed attribute id

        foreach ($products as $pIndex => $product) {
            foreach ($product->attributeValues as $pav) {
                $av        = $pav->attributeValue;
                $attribute = $av?->attribute;
                $group     = $attribute?->group;

                if (! $av || ! $attribute || ! $group) continue;

                $groupTitle = $this->locale->trans($group, 'title');
                $attrTitle  = $this->locale->trans($attribute, 'title');
                $value      = $this->locale->trans($av, 'value');

                $groupMap[$groupTitle][$attrTitle][$pIndex] = $value;

                $groupIds[$groupTitle]             = HasHashId::hashId($group->id);
                $attrIds[$groupTitle][$attrTitle]  = HasHashId::hashId($attribute->id);
            }
        }

        $productCount = $products->count();

        // ── Build attribute groups structure with is_same resolved ─────────
        // Pre-build the full groups array (used to determine is_same across products)
        $allGroups = [];
        foreach ($groupMap as $groupTitle => $attributes) {
            $attrRows = [];
            foreach ($attributes as $attrTitle => $valuesByIndex) {
                $values = [];
                for ($i = 0; $i < $productCount; $i++) {
                    $values[] = $valuesByIndex[$i] ?? null;
                }
                $nonNull = array_values(array_filter($values, fn ($v) => $v !== null));
                $isSame  = count($nonNull) === $productCount && count(array_unique($nonNull)) === 1;

                // Apply CompareFilter
                if ($filter === 'SAME' && ! $isSame) continue;
                if ($filter === 'DIFFERENT' && $isSame) continue;

                $attrRows[$attrTitle] = [
                    'id'      => $attrIds[$groupTitle][$attrTitle],
                    'values'  => $values,   // indexed by product position
                    'is_same' => $isSame,
                ];
            }
            if (! empty($attrRows)) {
                $allGroups[$groupTitle] = $attrRows;
            }
        }

        // ── Build per-product rows with their own attribute_groups slice ───
        $productRows = $products->values()->map(function (Product $p, int $pIndex) use ($allGroups, $groupIds, $attrIds) {
            $productGroups = [];

            foreach ($allGroups as $groupTitle => $attributes) {
                $attrRows = [];
                foreach ($attributes as $attrTitle => $info) {
                    $attrRows[] = [
                        'id'      => $info['id'],
                        'title'   => $attrTitle,
                        'value'   => $info['values'][$pIndex] ?? null,
                        'is_same' => $info['is_same'],
                    ];
                }
                $productGroups[] = [
                    'id'         => $groupIds[$groupTitle],
                    'title'      => $groupTitle,
                    'attributes' => $attrRows,
                ];
            }

            return [
                'id'               => HasHashId::hashId($p->id),
                'slug'             => $this->locale->trans($p, 'slug'),
                'title'            => $this->locale->trans($p, 'title'),
                'subtitle'         => $this->locale->trans($p, 'subtitle'),
                'image'            => $p->mainImage->first()?->path,
                'rrp'              => $p->rrp,
                'rrp_old'          => $p->rrp_old,
                'short_description' => $this->locale->trans($p, 'short_description'),
                'description'      => $this->locale->trans($p, 'description'),
                'brand'            => $p->brand
                    ? ['id' => HasHashId::hashId($p->brand->id), 'title' => $p->brand->title]
                    : null,
                'attribute_groups' => $productGroups,
            ];
        })->all();

        return ['products' => $productRows];
    }
}
