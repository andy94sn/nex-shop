<?php

declare(strict_types=1);

namespace Modules\Settings\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Settings\Models\SiteSettings;
use Illuminate\Support\Facades\Cache;

class SiteSettingsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): SiteSettings
    {
        return Cache::remember('shop.site_settings', (int) env('CACHE_TTL_SETTINGS', 86400), fn () =>
            SiteSettings::instance()
        );
    }
}
