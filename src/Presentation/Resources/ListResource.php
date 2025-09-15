<?php

declare(strict_types=1);

namespace Presentation\Resources;

use IteratorAggregate;
use JsonSerializable;
use Traversable;

class ListResource implements JsonSerializable, IteratorAggregate
{
    /**
     * @param array<JsonSerializable> $data
     * @return void
     */
    public function __construct(
        private array $data = []
    ) {
    }

    /**
     * @param JsonSerializable $data
     * @return void
     */
    public function pushData(JsonSerializable $data): void
    {
        $this->data[] = $data;
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return [
            'object' => 'list',
            'data' => $this->data
        ];
    }

    public function getIterator(): Traversable
    {
        yield from $this->data;
    }
}
