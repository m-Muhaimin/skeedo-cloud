<?php

declare(strict_types=1);

namespace Ai\Domain\Completion;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Generator;

interface CompletionServiceInterface extends AiServiceInterface
{
    /**
     * @return Generator<int,Chunk,null,Count>
     * @throws ApiException
     * @throws DomainException
     */
    public function generateCompletion(
        Model $model,
        array $params = [],
    ): Generator;
}
