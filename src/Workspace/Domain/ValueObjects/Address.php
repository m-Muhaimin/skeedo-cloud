<?php

declare(strict_types=1);

namespace Workspace\Domain\ValueObjects;

use JsonSerializable;
use Override;

class Address implements JsonSerializable
{
    public readonly ?string $country;
    public readonly ?string $state;
    public readonly ?string $city;
    public readonly ?string $line1;
    public readonly ?string $line2;
    public readonly ?string $zip;
    public readonly ?string $phoneNumber;

    public function __construct(?array $data = null)
    {
        $data = $data ?? [];

        $this->country = $data['country'] ?? null;
        $this->state = $data['state'] ?? null;
        $this->city = $data['city'] ?? null;
        $this->line1 = $data['line1'] ?? null;
        $this->line2 = $data['line2'] ?? null;
        $this->zip = $data['zip'] ?? null;
        $this->phoneNumber = $data['phone_number'] ?? null;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'zip' => $this->zip,
            'phone_number' => $this->phoneNumber,
        ];
    }
}
