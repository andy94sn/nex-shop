<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Settings\Models\SiteLink;
use Modules\Settings\Models\SiteSettings;

class FooterSettingsQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $s = SiteSettings::instance();

        return [
            'footer_text'      => $s->footer_text,
            'footer_logo'      => $s->footer_logo,
            'footer_logo_dark' => $s->footer_logo_dark,
            'social_links'     => SiteLink::activeOfType('social'),
            'messenger_links'  => SiteLink::activeOfType('messenger'),
            'payment_methods'  => SiteLink::activeOfType('payment'),
        ];
    }
}
