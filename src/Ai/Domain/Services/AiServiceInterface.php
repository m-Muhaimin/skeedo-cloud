<?php

declare(strict_types=1);

namespace Ai\Domain\Services;

use Ai\Domain\ValueObjects\Model;
use Traversable;

interface AiServiceInterface
{
    /**
     * @param Model $model 
     * @return bool 
     */
    public function supportsModel(Model $model): bool;

    /**
     * @return Traversable<int,Model>
     */
    public function getSupportedModels(): Traversable;
}
