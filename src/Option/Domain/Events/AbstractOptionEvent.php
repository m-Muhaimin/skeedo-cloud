<?php

declare(strict_types=1);

namespace Option\Domain\Events;

use Option\Domain\Entities\OptionEntity;

abstract class AbstractOptionEvent
{
    public function __construct(
        public readonly OptionEntity $option,
    ) {
    }
}
