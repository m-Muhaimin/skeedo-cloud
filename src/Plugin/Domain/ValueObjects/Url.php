<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Url implements JsonSerializable
{
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    public function jsonSerialize(): ?string
    {
        return $this->value;
    }

    private function ensureValueIsValid(?string $value)
    {
        if (is_null($value)) {
            return;
        }

        if (
            filter_var($value, FILTER_VALIDATE_URL) === false
            && $this->isValidPath($value) === false
        ) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>. Not a valid URL or path',
                static::class,
                $value
            ));
        }
    }

    private function isValidPath(string $path): bool
    {
        return preg_match('#^\/[a-zA-Z0-9\-\/_\.]*$#', $path) !== false;
    }
}
