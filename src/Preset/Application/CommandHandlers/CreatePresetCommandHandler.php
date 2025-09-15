<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Category\Application\Commands\ReadCategoryCommand;
use Preset\Application\Commands\CreatePresetCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Events\PresetCreatedEvent;
use Preset\Domain\Exceptions\LockedPresetException;
use Preset\Domain\Exceptions\TemplateExistsException;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Preset\Domain\ValueObjects\SortParameter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

class CreatePresetCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
        private Dispatcher $bus
    ) {}

    /**
     * @throws NoHandlerFoundException
     * @throws LockedPresetException
     * @throws TemplateExistsException
     */
    public function handle(CreatePresetCommand $cmd): PresetEntity
    {
        $preset = new PresetEntity($cmd->type, $cmd->title);

        if ($cmd->categoryId) {
            $command = new ReadCategoryCommand(
                (string) $cmd->categoryId->getValue()
            );

            $category = $this->bus->dispatch($command);
            $preset->setCategory($category);
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

        if ($cmd->lock === true) {
            $preset->lock();
        }

        $first = $this->repo->sort(SortDirection::ASC, SortParameter::POSITION)
            ->setMaxResults(new MaxResults(1))
            ->getIterator()
            ->current();

        if ($first) {
            $preset->placeBetween(null, $first);
        }

        // Check if preset with same template already exists
        if ($this->repo->ofTemplate($preset->getTemplate())) {
            throw new TemplateExistsException($preset->getTemplate());
        }

        // Add entoty to repository
        $this->repo->add($preset);

        // Dispatch the preset created event
        $event = new PresetCreatedEvent($preset);
        $this->dispatcher->dispatch($event);

        return $preset;
    }
}
