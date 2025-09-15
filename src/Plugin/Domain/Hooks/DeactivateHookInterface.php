<?php

declare(strict_types=1);

namespace Plugin\Domain\Hooks;

use Plugin\Domain\Context;

interface DeactivateHookInterface
{
    /**
     * @param Context $context
     * @return void
     */
    public function deactivate(Context $context): void;
}
