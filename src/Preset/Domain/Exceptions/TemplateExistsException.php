<?php

declare(strict_types=1);

namespace Preset\Domain\Exceptions;

use Exception;
use Preset\Domain\ValueObjects\Template;
use Throwable;

class TemplateExistsException extends Exception
{
    public function __construct(
        public readonly Template $template,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                "Template \"%s\" is already taken!",
                addslashes($template->value)
            ),
            $code,
            $previous
        );
    }
}
