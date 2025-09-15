<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use JsonSerializable;

class SupportChannel implements JsonSerializable
{
    public readonly SupportChannelType $type;
    public readonly Email|Url $value;

    public function __construct(
        string|SupportChannelType $type,
        string $value
    ) {
        $this->type = $type instanceof SupportChannelType
            ? $type : SupportChannelType::from($type);

        $this->value = $this->type == SupportChannelType::EMAIL
            ? new Email($value) : new Url($value);
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value
        ];
    }
}
