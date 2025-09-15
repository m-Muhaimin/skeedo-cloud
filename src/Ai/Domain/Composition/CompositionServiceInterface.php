<?php

declare(strict_types=1);

namespace Ai\Domain\Composition;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Traversable;

interface CompositionServiceInterface extends AiServiceInterface
{

    /**
     * @return Traversable<CompositionResponse>
     */
    public function generateComposition(
        Model $model,
        array $params = [],
    ): Traversable;
}
