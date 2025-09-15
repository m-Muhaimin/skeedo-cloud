<?php

declare(strict_types=1);

namespace Plugin\Domain\Hooks;

use Plugin\Domain\Context;

interface UninstallHookInterface
{
    /**
     * @param Context $context
     * @return void
     */
    public function uninstall(Context $context): void;
}
