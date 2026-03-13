<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\Page;
use Modules\Content\Models\ContactDepartment;

class ContactPageQuery
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $page        = Page::where('type', 'contact')->where('is_active', true)->first();
        $departments = ContactDepartment::where('is_active', true)->orderBy('sort')->get()->toArray();

        return [
            'title'            => $page?->title,
            'intro_text'       => $page?->intro_text,
            'map_lat'          => $page?->map_lat,
            'map_lng'          => $page?->map_lng,
            'meta_title'       => $page?->meta_title,
            'meta_description' => $page?->meta_description,
            'departments'      => $departments,
        ];
    }
}
