<?php

declare(strict_types=1);

namespace Voice\Application\CommandHandlers;

use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Speech\VoiceCloningServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Voice\Application\Commands\DeleteVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\VoiceRepositoyInterface;

class DeleteVoiceCommandHandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo,
        private AiServiceFactoryInterface $factory,
    ) {}

    /**
     * @throws VoiceNotFoundException
     */
    public function handle(DeleteVoiceCommand $cmd): void
    {
        $voice = $this->repo->ofId($cmd->id);

        if (!$voice->getUser()) {
            throw new VoiceNotFoundException(
                $cmd->id
            );
        }

        try {
            $this->deleteFromService($voice);
        } catch (\Throwable $th) {
            // Do nothing, unable to delete from service
        }

        $this->repo->remove($voice);
    }

    private function deleteFromService(VoiceEntity $voice): void
    {
        $service = $this->factory->create(
            VoiceCloningServiceInterface::class,
            new Model($voice->getProvider()->value)
        );

        $service->deleteVoice($voice->getExternalId()->value);
    }
}
