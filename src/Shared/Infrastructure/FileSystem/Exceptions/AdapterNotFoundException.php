<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem\Exceptions;

use Exception;
use Throwable;

class AdapterNotFoundException extends Exception
{
    public function __construct(
        public readonly string $adapterName,
        $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Adapter not found: " . $adapterName,
            $code,
            $previous
        );
    }
}
