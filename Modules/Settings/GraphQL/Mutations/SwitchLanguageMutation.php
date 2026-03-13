<?php

declare(strict_types=1);

namespace Modules\Settings\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Settings\Models\Language;
use GraphQL\Error\Error;

class SwitchLanguageMutation
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): Language
    {
        $code     = $args['code'];
        $language = Language::active()->firstWhere('code', $code);

        if (! $language) {
            throw new Error("Language '{$code}' is not available.");
        }

        request()->session()->put('locale', $code);
        request()->session()->save();

        return $language;
    }
}
