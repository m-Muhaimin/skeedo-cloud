<?php

declare(strict_types=1);

namespace Workspace\Domain\Exceptions;

use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class WorkspaceUserNotFoundException extends Exception
{
    /**
     * @param Id $id Workspace user id
     * @param int $code Exception code
     * @param null|Throwable $previous Previous exception
     * @return void Returns nothing
     */
    public function __construct(
        public readonly Id $id,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Workspace user with id <%s> doesn't exists!",
                $id->getValue()
            ),
            $code,
            $previous
        );
    }
}
