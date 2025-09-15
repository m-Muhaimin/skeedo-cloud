<?php

declare(strict_types=1);

namespace Voice\Application\CommandHandlers;

use Ai\Domain\ValueObjects\Model;
use Voice\Application\Commands\CountVoicesCommand;
use Voice\Domain\VoiceRepositoyInterface;

class CountVoicesCommandhandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo,
    ) {}

    public function handle(CountVoicesCommand $cmd): int
    {
        $voices = $this->repo;

        if ($cmd->combined && $cmd->workspace && $cmd->user) {
            $voices = $voices->filterByAccess($cmd->user, $cmd->workspace);
        } else {
            if ($cmd->workspace) {
                $voices = $voices->filterByWorkspace($cmd->workspace);
            }

            if ($cmd->user) {
                $voices = $voices->filterByUser($cmd->user);
            }
        }

        $models = array_filter(
            $cmd->models ?: [],
            fn($model) => $model instanceof Model
        );

        if ($models) {
            $voices = $voices->filterByModel(...$models);
        }

        if ($cmd->status) {
            $voices = $voices->filterByStatus($cmd->status);
        }

        if ($cmd->provider) {
            $voices = $voices->filterByProvider($cmd->provider);
        }

        if ($cmd->tone) {
            $voices = $voices->filterByTone($cmd->tone);
        }

        if ($cmd->useCase) {
            $voices = $voices->filterByUseCase($cmd->useCase);
        }

        if ($cmd->gender) {
            $voices = $voices->filterByGender($cmd->gender);
        }

        if ($cmd->accent) {
            $voices = $voices->filterByAccent($cmd->accent);
        }

        if ($cmd->languageCode) {
            $voices = $voices->filterByLanguage($cmd->languageCode);
        }

        if ($cmd->age) {
            $voices = $voices->filterByAge($cmd->age);
        }

        if ($cmd->query) {
            $voices = $voices->search($cmd->query);
        }

        return $voices->count();
    }
}
