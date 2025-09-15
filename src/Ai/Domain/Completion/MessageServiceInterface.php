<?php

declare(strict_types=1);

namespace Ai\Domain\Completion;

use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;

interface MessageServiceInterface extends AiServiceInterface
{
    /**
     * @return Generator<int,Chunk,null,CreditCount>
     * @throws ApiException
     * @throws DomainException
     */
    public function generateMessage(
        Model $model,
        MessageEntity $message,
    ): Generator;
}
