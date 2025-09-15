<?php

namespace User\Domain\Exceptions;

use Exception;
use Throwable;

class OwnedWorkspaceCapException extends Exception
{
    public function __construct(
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            'Maximum number of owned workspaces reached.',
            $code,
            $previous
        );
    }
}
