<?php

declare(strict_types=1);

namespace Presentation\Resources;

use JsonSerializable;
use Symfony\Component\Intl\Countries;

class CountryResource implements JsonSerializable
{
    public function __construct(
        private ?string $alphaCode
    ) {
    }

    public function jsonSerialize(): ?array
    {
        if (!$this->alphaCode) {
            return null;
        }

        $alpha = strtoupper($this->alphaCode);
        $alpha2 = strlen($alpha) == 2 &&  Countries::exists($alpha) ? $alpha : null;
        $alpha3 = strlen($alpha) == 3 && Countries::alpha3CodeExists($alpha) ? $alpha : null;

        if (!$alpha2 && !$alpha3) {
            return null;
        }

        if (!$alpha3) {
            $alpha3 = Countries::getAlpha3Code($alpha2);
        }

        if (!$alpha2) {
            $alpha2 = Countries::getAlpha2Code($alpha3);
        }

        return [
            'name' => Countries::getName($alpha2),
            'alpha2' => $alpha2,
            'alpha3' => $alpha3,
            'flag_url' => 'https://flagcdn.com/' . strtolower($alpha2) . '.svg'
        ];
    }
}
