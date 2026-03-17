<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Core\Services\LocaleService;
use Modules\Settings\Models\SiteLink;
use Modules\Settings\Models\SiteSettings;

class LayoutQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $s = SiteSettings::instance();

        return [
            // ── Header ────────────────────────────────────────────────────
            'header' => [
                'site_name'      => $s->site_name,
                'site_logo'      => $s->site_logo,
                'site_favicon'   => $s->site_favicon,
                'contact_phone'  => $s->contact_phone,
                'contact_email'  => $s->contact_email,
                'messenger_links' => SiteLink::activeOfType('messenger'),
            ],

            // ── Footer ────────────────────────────────────────────────────
            'footer' => [
                'footer_text'      => $this->locale->trans($s, 'footer_text'),
                'footer_logo'      => $s->footer_logo,
                'footer_logo_dark' => $s->footer_logo_dark,
                'social_links'     => SiteLink::activeOfType('social'),
                'messenger_links'  => SiteLink::activeOfType('messenger'),
                'payment_methods'  => SiteLink::activeOfType('payment'),
            ],

            // ── Global SEO defaults ───────────────────────────────────────
            'seo' => [
                'title'       => $this->locale->trans($s, 'seo_title'),
                'description' => $this->locale->trans($s, 'seo_description'),
                'og_image'    => $s->seo_og_image,
            ],
        ];
    }
}
