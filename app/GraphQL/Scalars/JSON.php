<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Passes any JSON-serialisable value through unchanged.
 */
class JSON extends ScalarType
{
    public string $name = 'JSON';

    public ?string $description = 'Arbitrary JSON data.';

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
        return match (true) {
            $valueNode instanceof StringValueNode,
            $valueNode instanceof IntValueNode,
            $valueNode instanceof FloatValueNode  => $valueNode->value,
            $valueNode instanceof BooleanValueNode => $valueNode->value,
            $valueNode instanceof NullValueNode    => null,
            default                               => null,
        };
    }
}
