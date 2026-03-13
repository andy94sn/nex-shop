<?php

declare(strict_types=1);

namespace Modules\Marketing\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use Modules\Marketing\Models\Subscriber;

class SubscribeMutation
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $exists = Subscriber::where('email', $args['email'])->exists();

        if ($exists) {
            return ['success' => false, 'message' => 'Acest email este deja abonat.'];
        }

        Subscriber::create([
            'email'         => $args['email'],
            'name'          => $args['name'] ?? null,
            'phone'         => $args['phone'] ?? null,
            'subscribed_at' => now(),
            'is_active'     => true,
            'locale'        => app()->getLocale(),
        ]);

        return ['success' => true, 'message' => 'Te-ai abonat cu succes!'];
    }
}
