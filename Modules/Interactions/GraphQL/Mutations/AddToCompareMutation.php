<?php

declare(strict_types=1);

namespace Modules\Interactions\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Error\UserError;
use Modules\Interactions\Services\CompareService;

class AddToCompareMutation
{
    public function __construct(private readonly CompareService $compare) {}

    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $info): array
    {
        $sessionId = request()->session()->getId();
        $result    = $this->compare->add($sessionId, $args['article']);

        if (! $result['success']) {
            $message = match ($result['error'] ?? '') {
                'already_added' => 'Produsul este deja în lista de comparație.',
                'max_reached'   => 'Poți compara maxim ' . env('COMPARE_MAX_PER_CATEGORY', 3) . ' produse din aceeași categorie.',
                default         => 'Nu s-a putut adăuga produsul.',
            };
            throw new UserError($message);
        }

        return ['success' => true, 'error' => null];
    }
}
