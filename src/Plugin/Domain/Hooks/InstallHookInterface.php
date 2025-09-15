<?php

declare(strict_types=1);

namespace Plugin\Domain\Hooks;

use Plugin\Domain\Context;

interface InstallHookInterface
{
    /**
     * @param Context $context
     * @return void
     */
    public function install(Context $context): void;
}
