<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Category\Application\Commands\ReadCategoryCommand;
use Preset\Application\Commands\UpdatePresetCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Events\PresetUpdatedEvent;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Exceptions\LockedPresetException;
use Preset\Domain\Exceptions\TemplateExistsException;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

class UpdatePresetCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
        private Dispatcher $bus
    ) {}

    /**
     * @throws PresetNotFoundException
     * @throws NoHandlerFoundException
     * @throws LockedPresetException
     * @throws TemplateExistsException
     */
    public function handle(UpdatePresetCommand $cmd): PresetEntity
    {
        $preset = $this->repo->ofId($cmd->id);

        if ($cmd->categoryId) {
            $command = new ReadCategoryCommand((string) $cmd->categoryId->getValue());
            $category = $this->bus->dispatch($command);
            $preset->setCategory($category);
        } elseif ($cmd->removeCategory === true) {
            $preset->setCategory(null);
        }

        if ($cmd->title) {
            $preset->setTitle($cmd->title);
        }

        if ($cmd->description) {
            $preset->setDescription($cmd->description);
        }

        if ($cmd->status) {
            $preset->setStatus($cmd->status);
        }

        if ($cmd->template) {
            $preset->setTemplate($cmd->template);
        }

        if ($cmd->image) {
            $preset->setImage($cmd->image);
        }

        if ($cmd->color) {
            $preset->setColor($cmd->color);
        }

        $otherPreset = $this->repo->ofTemplate($preset->getTemplate());

        if ($otherPreset && $otherPreset->getId() !== $preset->getId()) {
            throw new TemplateExistsException($preset->getTemplate());
        }

        if ($cmd->after || $cmd->before) {
            $after = $cmd->after ? $this->repo->ofId($cmd->after) : null;
            $before = $cmd->before ? $this->repo->ofId($cmd->before) : null;
            $preset->placeBetween($after, $before);
        }

        // Dispatch the preset updated event
        $event = new PresetUpdatedEvent($preset);
        $this->dispatcher->dispatch($event);

        return $preset;
    }
}
