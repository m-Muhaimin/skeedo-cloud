<?php

declare(strict_types=1);

namespace Ai\Domain\Exceptions;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Exception;
use Throwable;

class ModelNotSupportedException extends Exception
{
    /**
     * @param class-string<AiServiceInterface> $service 
     */
    public function __construct(
        public readonly string $service,
        public readonly Model $model,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Model "%s" is not supported by service "%s".',
            $model->value,
            $service
        );

        parent::__construct($message, $code, $previous);
    }
}
