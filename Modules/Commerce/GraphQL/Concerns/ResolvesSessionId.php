<?php

declare(strict_types=1);

namespace Modules\Commerce\GraphQL\Concerns;

trait ResolvesSessionId
{
    protected function sessionId(): string
    {
        return request()->session()->getId();
    }
}
