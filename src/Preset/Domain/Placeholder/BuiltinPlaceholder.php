<?php

declare(strict_types=1);

namespace Preset\Domain\Placeholder;

use Override;

class BuiltinPlaceholder implements PlaceholderInterface
{
    public string $type;

    public function __construct(public string $name)
    {
        $this->type = $this->name;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->name,
            'is_builtin' => true
        ];
    }
}
