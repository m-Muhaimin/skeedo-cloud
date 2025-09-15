<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

use Ai\Domain\ValueObjects\Model;
use InvalidArgumentException;

class ModelNotSupportedException extends InvalidArgumentException
{
    public function __construct(readonly public Model $model)
    {
        parent::__construct(sprintf('Model %s is not supported', $model->value));
    }
}
