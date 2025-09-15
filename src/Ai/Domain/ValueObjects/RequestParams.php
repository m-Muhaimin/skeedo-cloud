<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use stdClass;

class RequestParams extends stdClass
{
    public static function fromArray(array $data): self
    {
        $request = new self();
        foreach ($data as $key => $value) {
            $request->{$key} = $value;
        }

        return $request;
    }
}
