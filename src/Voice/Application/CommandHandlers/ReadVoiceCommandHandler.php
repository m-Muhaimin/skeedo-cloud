<?php

declare(strict_types=1);

namespace Voice\Application\CommandHandlers;

use Voice\Application\Commands\ReadVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\VoiceRepositoyInterface;

class ReadVoiceCommandHandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo
    ) {
    }

    /**
     * @throws VoiceNotFoundException
     */
    public function handle(ReadVoiceCommand $cmd): VoiceEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
