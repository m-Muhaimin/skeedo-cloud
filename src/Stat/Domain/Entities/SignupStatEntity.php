<?php

declare(strict_types=1);

namespace Stat\Domain\Entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\ValueObjects\CountryCode;
use Stat\Domain\ValueObjects\Metric;

#[ORM\Entity]
class SignupStatEntity extends AbstractStatEntity
{
    #[ORM\Column(type: Types::STRING, enumType: CountryCode::class, name: 'country_code', nullable: true)]
    private ?CountryCode $countryCode = null;

    public function __construct(
        ?CountryCode $countryCode = null
    ) {
        parent::__construct();

        $this->incrementMetric(
            new Metric(1)
        );

        $this->countryCode = $countryCode;
    }

    public function getCountryCode(): ?CountryCode
    {
        return $this->countryCode;
    }
}
