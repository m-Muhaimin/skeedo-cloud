<?php

declare(strict_types=1);

namespace Ai\Domain\Speech;

use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Services\AiServiceInterface;
use Traversable;

interface SpeechServiceInterface extends AiServiceInterface
{
    /** @return Traversable<int,VoiceEntity> */
    public function getVoiceList(): Traversable;

    /**
     * @param VoiceEntity $voice
     * @param array $params
     * @return GenerateSpeechResponse
     * @throws DomainException
     * @throws ApiException
     */
    public function generateSpeech(
        VoiceEntity $voice,
        array $params = [],
    ): GenerateSpeechResponse;
}
