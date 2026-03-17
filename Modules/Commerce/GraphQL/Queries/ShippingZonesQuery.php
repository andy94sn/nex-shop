<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Models\ShippingZone;
use Modules\Commerce\Services\ShippingZoneService;
use Modules\Core\Services\LocaleService;

/**
 * Returns all active shipping zones with their geographic coverage,
 * so the frontend can render a zone picker or inform the user about
 * available shipping options.
 */
class ShippingZonesQuery
{
    public function __construct(
        private readonly LocaleService $locale,
        private readonly ShippingZoneService $zoneService,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        return ShippingZone::with(['countries', 'cities.country'])
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (ShippingZone $z) => [
                'id'           => $z->id,
                'name'         => $this->locale->trans($z, 'name'),
                'default_cost' => $z->default_cost,
                'countries'    => $z->countries->map(fn ($c) => [
                    'id'   => $c->id,
                    'code' => $c->code,
                    'name' => $this->locale->trans($c, 'name'),
                ])->toArray(),
                'cities'       => $z->cities->map(fn ($city) => [
                    'id'   => $city->id,
                    'name' => $this->locale->trans($city, 'name'),
                    'country' => [
                        'id'   => $city->country->id,
                        'code' => $city->country->code,
                        'name' => $this->locale->trans($city->country, 'name'),
                    ],
                ])->toArray(),
            ])
            ->toArray();
    }
}
