<?php

declare(strict_types=1);

namespace Stat\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Stat\Domain\ValueObjects\Metric;

#[ORM\Entity]
class OrderStatEntity extends AbstractStatEntity
{
    public function __construct()
    {
        parent::__construct();
        $this->incrementMetric(
            new Metric(1)
        );
    }
}
