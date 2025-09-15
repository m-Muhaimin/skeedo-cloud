<?php

declare(strict_types=1);

namespace Ai\Domain\Services;

use Ai\Domain\ValueObjects\Model;
use Traversable;

interface AiServiceFactoryInterface
{
    /**
     * Get/create a AI service for a given service class string.
     *
     * @template T of AiServiceInterface
     * @param class-string<T> $name 
     * @param Model $model 
     * @return T
     */
    public function create(
        string $name,
        Model $model
    ): AiServiceInterface;

    /**
     * @template T of AiServiceInterface
     * @param class-string<T>  $name
     * @return Traversable<int,T>
     */
    public function list(string $name): Traversable;
}
