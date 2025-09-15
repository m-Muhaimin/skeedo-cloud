<?php

declare(strict_types=1);

namespace Category\Domain\Events;

use Category\Domain\Entities\CategoryEntity;

abstract class AbstractCategoryEvent
{
    public function __construct(
        public readonly CategoryEntity $user
    ) {
    }
}
