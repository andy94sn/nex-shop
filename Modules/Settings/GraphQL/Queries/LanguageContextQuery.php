<?php

declare(strict_types=1);

namespace Modules\Settings\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Settings\Models\Language;

class LanguageContextQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $currentCode = Language::resolveLocale();
        $active      = Language::active();

        return [
            'current'   => $active->firstWhere('code', $currentCode) ?? Language::default(),
            'available' => $active->values()->all(),
        ];
    }
}
