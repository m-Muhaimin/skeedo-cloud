<?php

declare(strict_types=1);

namespace Ai\Domain\IsolatedVoice;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Psr\Http\Message\StreamInterface;

interface VoiceIsolatorServiceInterface extends AiServiceInterface
{
    public function generateIsolatedVoice(
        Model $model,
        StreamInterface $file,
        array $params = [],
    ): IsolatedVoiceResponse;
}
