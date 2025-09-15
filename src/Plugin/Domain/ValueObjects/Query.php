<?php

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;

class Query
{
    private ?string $value;

    /**
     * @param null|string $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(?string $value = null)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    /** @return null|string  */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param null|string $value
     * @return void
     * @throws InvalidArgumentException
     */
    private function ensureValueIsValid(?string $value): void
    {
        if (!is_null($value) && mb_strlen($value) > 150) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>. Maximum <%s> characters allowed.',
                static::class,
                $value,
                80
            ));
        }
    }
}
