<?php

declare(strict_types=1);

namespace Workspace\Domain\Exceptions;

use Exception;
use Throwable;

class MemberCapExceededException extends Exception
{
    public function __construct(
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            'Maximum number of members reached.',
            $code,
            $previous
        );
    }
}
