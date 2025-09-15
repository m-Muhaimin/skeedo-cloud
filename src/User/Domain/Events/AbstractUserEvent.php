<?php

declare(strict_types=1);

namespace User\Domain\Events;

use User\Domain\Entities\UserEntity;

abstract class AbstractUserEvent
{
    public function __construct(
        public readonly UserEntity $user
    ) {
    }
}
