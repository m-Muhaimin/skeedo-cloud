<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class PhoneNumber implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "phone_number", nullable: true, length: 30)]
    public readonly ?string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(?string $value = null)
    {
        $this->value = $this->ensureValueIsValid($value);
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueException
     */
    private function ensureValueIsValid(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $pno = $util->parse($value);

            if ($util->isValidNumber($pno)) {
                return $util->format($pno, PhoneNumberFormat::E164);
            }
        } catch (NumberParseException $e) {
            // Do nothing
        }

        throw new InvalidValueException(sprintf(
            '%s does not allow the value %s.',
            static::class,
            $value
        ));
    }
}
