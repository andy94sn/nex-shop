<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Queries;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\Page;
use Modules\Content\Models\PageAttachment;
use Modules\Content\Models\PageImage;
use Modules\Core\Services\LocaleService;

class PageQuery
{
    public function __construct(
        private readonly LocaleService $locale,
    ) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): ?array
    {
        $locale = $this->locale->get();

        $page = Page::with(['images', 'attachments'])
            ->where('slug', $args['slug'])
            ->where('is_active', true)
            ->first();

        if (! $page) {
            return null;
        }

        return [
            'id'               => $page->id,
            'slug'             => $page->slug,
            'title'            => $this->locale->trans($page, 'title'),
            'content'          => $this->locale->trans($page, 'content'),
            'type'             => $page->type,
            'meta_title'       => $this->locale->trans($page, 'meta_title'),
            'meta_description' => $this->locale->trans($page, 'meta_description'),
            'images'           => $page->images->map(fn (PageImage $img) => [
                'id'       => $img->id,
                'path'     => $img->path,
                'alt'      => $this->locale->trans($img, 'alt'),
                'caption'  => $this->locale->trans($img, 'caption'),
                'is_cover' => $img->is_cover,
                'sort'     => $img->sort,
            ])->toArray(),
            'attachments'      => $page->attachments->map(fn (PageAttachment $att) => [
                'id'        => $att->id,
                'path'      => $att->path,
                'label'     => $this->locale->trans($att, 'label'),
                'mime_type' => $att->mime_type,
                'size'      => $att->size,
                'sort'      => $att->sort,
            ])->toArray(),
        ];
    }
}
