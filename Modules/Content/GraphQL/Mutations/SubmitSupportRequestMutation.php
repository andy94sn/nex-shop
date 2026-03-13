<?php

declare(strict_types=1);

namespace Modules\Content\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Content\Models\SupportRequest;
use Illuminate\Support\Facades\Mail;

class SubmitSupportRequestMutation
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $request = SupportRequest::create([
            'name'        => $args['name'],
            'email'       => $args['email'],
            'message'     => $args['message'],
            'page_source' => $args['page_source'] ?? null,
            'status'      => 'new',
        ]);

        // Notify admin (non-blocking, queued)
        try {
            Mail::to(env('ADMIN_EMAIL'))->queue(
                new \Modules\Content\Mail\SupportRequestMail($request)
            );
        } catch (\Throwable) {
            // silently fail — don't break the response
        }

        return ['success' => true, 'message' => 'Mesajul a fost trimis cu succes.'];
    }
}
