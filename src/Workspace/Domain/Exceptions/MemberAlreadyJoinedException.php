<?php

declare(strict_types=1);

namespace Workspace\Domain\Exceptions;

use Exception;
use Throwable;
use Workspace\Domain\ValueObjects\Email;

class MemberAlreadyJoinedException extends Exception
{
    public function __construct(
        public readonly Email $email,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Member with email <%s> already joined!",
                $email->value
            ),
            $code,
            $previous
        );
    }
}
