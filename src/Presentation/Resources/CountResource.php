<?php

declare(strict_types=1);

namespace Presentation\Resources;

use JsonSerializable;

class CountResource implements JsonSerializable
{
    public function __construct(private int $count)
    {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'count' => $this->count,
        ];
    }
}
