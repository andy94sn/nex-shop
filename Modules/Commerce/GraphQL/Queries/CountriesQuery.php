<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Commerce\Models\Country;
use Modules\Core\Services\LocaleService;

/**
 * Returns active countries with their cities — used for the
 * shipping address / location picker on the checkout page.
 */
class CountriesQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        return Country::with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (Country $country) => [
                'id'     => $country->id,
                'code'   => $country->code,
                'name'   => $this->locale->trans($country, 'name'),
                'cities' => $country->cities->map(fn ($city) => [
                    'id'   => $city->id,
                    'name' => $this->locale->trans($city, 'name'),
                ])->toArray(),
            ])
            ->toArray();
    }
}
