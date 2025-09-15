<?php

declare(strict_types=1);

namespace Preset\Domain\Events;

use Preset\Domain\Entities\PresetEntity;

abstract class AbstractPresetEvent
{
    public function __construct(
        public readonly PresetEntity $user
    ) {
    }
}
