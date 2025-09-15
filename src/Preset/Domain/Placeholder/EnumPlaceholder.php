<?php

declare(strict_types=1);

namespace Preset\Domain\Placeholder;

use Override;

class EnumPlaceholder extends AbstractPlaceholder implements
    PlaceholderInterface
{
    public array $options = [];

    public function __construct(
        public string $name
    ) {
        parent::__construct($name, Type::ENUM);
    }

    public function addOption(string $value, ?string $label = null): static
    {
        $this->options[] = new Option($value, $label ?: $value);

        return $this;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return array_merge(
            $this->toArray(),
            [
                'options' => $this->options
            ]
        );
    }
}
