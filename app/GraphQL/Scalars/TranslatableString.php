<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Passes any string (or null) through unchanged.
 * Used for translatable fields that return the locale-resolved string.
 */
class TranslatableString extends ScalarType
{
    public string $name = 'TranslatableString';

    public ?string $description = 'A translatable string value resolved to the current locale.';

    public function serialize(mixed $value): mixed
    {
        return $value;
    }

    public function parseValue(mixed $value): mixed
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        return $valueNode instanceof StringValueNode ? $valueNode->value : null;
    }
}
