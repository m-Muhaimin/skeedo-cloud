<?php

declare(strict_types=1);

namespace Assistant\Domain\Exceptions;

use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class AssistantNotFoundException extends Exception
{
    public function __construct(
        public readonly Id $id,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                "Assistant with id <%s> doesn't exists!",
                $id->getValue()
            ),
            $code,
            $previous
        );
    }
}
