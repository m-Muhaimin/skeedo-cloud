<?php

declare(strict_types=1);

namespace Plugin\Domain\Hooks;

use Plugin\Domain\Context;

interface ActivateHookInterface
{
    /**
     * @param Context $context
     * @return void
     */
    public function activate(Context $context): void;
}
