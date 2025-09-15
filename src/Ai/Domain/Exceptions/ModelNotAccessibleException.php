<?php

declare(strict_types=1);

namespace Ai\Domain\Exceptions;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Exception;
use Throwable;

class ModelNotAccessibleException extends Exception
{
    public function __construct(
        public readonly Model $model,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Model "%s" is not accessible in your plan.',
            $model->value,
        );

        parent::__construct($message, $code, $previous);
    }
}
