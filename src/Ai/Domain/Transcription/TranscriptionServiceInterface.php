<?php

declare(strict_types=1);

namespace Ai\Domain\Transcription;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use DomainException;
use Psr\Http\Message\StreamInterface;

interface TranscriptionServiceInterface extends AiServiceInterface
{
    /**
     * @throws DomainException
     * @throws ApiException
     */
    public function generateTranscription(
        Model $model,
        StreamInterface $file,
        array $params = [],
    ): GenerateTranscriptionResponse;
}
