<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\FaqItem;
use Modules\Core\Services\LocaleService;

class FaqItemsQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $items = FaqItem::where('is_active', true)
            ->orderBy('sort')
            ->get();

        return $items->map(fn(FaqItem $f) => [
            'id'        => $f->id,
            'question'  => $this->locale->trans($f, 'question'),
            'answer'    => $this->locale->trans($f, 'answer'),
            'sort'      => $f->sort,
        ])->toArray();
    }
}
