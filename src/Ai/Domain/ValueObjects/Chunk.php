<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Ai\Domain\Entities\AbstractLibraryItemEntity;
use JsonSerializable;
use Override;

class Chunk implements JsonSerializable
{
    private array $attributes = [];
    public readonly Token|Call|AbstractLibraryItemEntity|ReasoningToken $data;

    public function __construct(
        string|Token|Call|AbstractLibraryItemEntity|ReasoningToken $value = ''
    ) {
        $this->data = is_string($value) ? new Token($value) : $value;
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        return [
            'data' => $this->data,
            'attributes' => $this->attributes,
        ];
    }

    public function withAttribute(string $key, mixed $value): static
    {
        $obj = clone $this;
        $obj->attributes[$key] = $value;
        return $obj;
    }
}
