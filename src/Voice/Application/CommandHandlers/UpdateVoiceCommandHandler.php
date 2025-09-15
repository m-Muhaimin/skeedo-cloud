<?php

namespace Voice\Application\CommandHandlers;

use Voice\Application\Commands\UpdateVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\VoiceRepositoyInterface;

class UpdateVoiceCommandHandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo
    ) {}

    /**
     * @throws VoiceNotFoundException
     */
    public function handle(UpdateVoiceCommand $cmd): VoiceEntity
    {
        $voice = $cmd->voice instanceof VoiceEntity
            ? $cmd->voice : $this->repo->ofId($cmd->voice);

        if ($cmd->status) {
            $voice->setStatus($cmd->status);
        }

        if ($cmd->name) {
            $voice->setName($cmd->name);
        }

        if ($cmd->sampleUrl) {
            $voice->setSampleUrl($cmd->sampleUrl);
        }

        if ($cmd->tones) {
            $voice->setTones(...$cmd->tones);
        }

        if ($cmd->useCases) {
            $voice->setUseCases(...$cmd->useCases);
        }

        if ($cmd->gender !== false) {
            $voice->setGender($cmd->gender);
        }

        if ($cmd->accent) {
            $voice->setAccent($cmd->accent);
        }

        if ($cmd->age !== false) {
            $voice->setAge($cmd->age);
        }

        if ($cmd->visibility) {
            $voice->setVisibility($cmd->visibility);
        }

        if ($cmd->before || $cmd->after) {
            $before = $cmd->before ? $this->repo->ofId($cmd->before) : null;
            $after = $cmd->after ? $this->repo->ofId($cmd->after) : null;
            $voice->placeBetween($after, $before);
        }

        return $voice;
    }
}
