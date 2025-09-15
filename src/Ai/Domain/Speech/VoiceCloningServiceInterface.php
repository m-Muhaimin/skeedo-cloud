<?php

declare(strict_types=1);

namespace Ai\Domain\Speech;

use Ai\Domain\Services\AiServiceInterface;
use Psr\Http\Message\StreamInterface;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;

interface VoiceCloningServiceInterface extends AiServiceInterface
{
    public function cloneVoice(
        string $name,
        StreamInterface $file,
        UserEntity $user,
    ): VoiceEntity;

    public function deleteVoice(string $id): void;
}
