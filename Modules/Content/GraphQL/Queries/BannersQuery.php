<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\ShopBanner;
use Modules\Core\Services\LocaleService;

class BannersQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $locale = $this->locale->get();

        return ShopBanner::activeAndValid()
            ->get()
            ->map(fn (ShopBanner $b) => [
                'id'           => $b->id,
                'title'        => $this->locale->trans($b, 'title'),
                'image'        => $this->locale->trans($b, 'image'),
                'image_mobile' => $this->locale->trans($b, 'image_mobile'),
                'url'          => $b->url,
                'sort'         => $b->sort,
                'valid_from'   => $b->valid_from,
                'valid_until'  => $b->valid_until,
            ])
            ->toArray();
    }
}
