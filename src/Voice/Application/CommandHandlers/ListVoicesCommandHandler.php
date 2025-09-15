<?php

declare(strict_types=1);

namespace Voice\Application\CommandHandlers;

use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Speech\SpeechServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Shared\Domain\ValueObjects\CursorDirection;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Traversable;
use Voice\Application\Commands\ListVoicesCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\VoiceRepositoyInterface;

class ListVoicesCommandHandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo,
        private AiServiceFactoryInterface $factory,
        private Dispatcher $dispatcher,

        #[Inject('option.voice_list_fetch_date')]
        private ?string $fetchedAt = null
    ) {}

    /**
     * @return Traversable<int,VoiceEntity>
     * @throws VoiceNotFoundException
     */
    public function handle(ListVoicesCommand $cmd): Traversable
    {
        $this->fetchVoices();

        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $items = $this->repo;

        if ($cmd->combined && $cmd->workspace && $cmd->user) {
            $items = $items->filterByAccess($cmd->user, $cmd->workspace);
        } else {
            if ($cmd->workspace) {
                $items = $items->filterByWorkspace($cmd->workspace);
            }

            if ($cmd->user) {
                $items = $items->filterByUser($cmd->user);
            }
        }

        $models = array_filter(
            $cmd->models ?: [],
            fn($model) => $model instanceof Model
        );

        if ($models) {
            $items = $items->filterByModel(...$models);
        }

        if ($cmd->sortDirection) {
            $items = $items->sort($cmd->sortDirection, $cmd->sortParameter);
        }

        if ($cmd->status) {
            $items = $items->filterByStatus($cmd->status);
        }

        if ($cmd->provider) {
            $items = $items->filterByProvider($cmd->provider);
        }

        if ($cmd->tone) {
            $items = $items->filterByTone($cmd->tone);
        }

        if ($cmd->useCase) {
            $items = $items->filterByUseCase($cmd->useCase);
        }

        if ($cmd->gender) {
            $items = $items->filterByGender($cmd->gender);
        }

        if ($cmd->accent) {
            $items = $items->filterByAccent($cmd->accent);
        }

        if ($cmd->languageCode) {
            $items = $items->filterByLanguage($cmd->languageCode);
        }

        if ($cmd->age) {
            $items = $items->filterByAge($cmd->age);
        }

        if ($cmd->query) {
            $items = $items->search($cmd->query);
        }

        if ($cmd->maxResults) {
            $items = $items->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $items = $items->endingBefore($cursor);
            }

            return $items->startingAfter($cursor);
        }

        return $items->getIterator();
    }

    private function fetchVoices(): void
    {
        if ($this->fetchedAt && $this->fetchedAt + 3600 >= time()) {
            // Voices were fetched less than an hour ago
            return;
        }

        $news = [];
        $available = [];
        $providers = [];

        foreach ($this->factory->list(SpeechServiceInterface::class) as $service) {
            try {
                foreach ($service->getVoiceList() as $newVoice) {
                    $key = $newVoice->getProvider()->value
                        . '-'
                        . $newVoice->getExternalId()->value;

                    $news[$key] = $newVoice;

                    if (!in_array($newVoice->getProvider()->value, $providers)) {
                        $providers[] = $newVoice->getProvider()->value;
                    }
                }
            } catch (\Throwable $e) {
                // Failed to fetch voices from provider, continue
            }
        }

        /** @var VoiceEntity $current */
        foreach ($this->repo as $current) {
            $key = $current->getProvider()->value
                . '-'
                . $current->getExternalId()->value;

            if (
                !isset($news[$key]) // Could not find voice in new list
                && in_array($current->getProvider()->value, $providers) // Fetched new voices from this provider
            ) {
                // Voice no longer available
                $this->repo->remove($current);
                continue;
            }

            $available[$key] = $current;
        }

        /** @var VoiceEntity $new */
        foreach ($news as $key => $new) {
            if (!isset($available[$key])) {
                // New voice
                $this->repo->add($new);
                continue;
            }

            $current = $available[$key];

            if ($current->getUpdatedAt() !== null) {
                // Voice already exists
                continue;
            }

            // Update voice
            $current->setName($new->getName());
            $current->setSampleUrl($new->getSampleUrl());
            $current->setTones(...$new->getTones());
            $current->setUseCases(...$new->getUseCases());
            $current->setGender($new->getGender());
            $current->setAccent($new->getAccent());
            $current->setAge($new->getAge());
            $current->setSupportedLanguages(...$new->getSupportedLanguages());
        }

        foreach ($available as $key => $current) {
            if (!isset($news[$key])) {
                // Voice no longer available
                $this->repo->remove($current);
            }
        }

        // Save fetch date
        $cmd = new SaveOptionCommand(
            'voice_list_fetch_date',
            (string) time()
        );

        $this->dispatcher->dispatch($cmd);
    }
}
