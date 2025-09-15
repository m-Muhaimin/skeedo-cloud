<?php

declare(strict_types=1);

namespace Shared\Infrastructure;

use Application;

interface ServiceProviderInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void;
}
