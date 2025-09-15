<?php

declare(strict_types=1);

namespace Ai\Domain\Classification;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use DomainException;

interface ClassificationServiceInterface extends AiServiceInterface
{
    /**
     * @throws DomainException
     * @throws ApiException
     */
    public function generateClassification(
        Model $model,
        string $input,
    ): ClassificationResponse;
}
