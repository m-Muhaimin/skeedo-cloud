<?php

declare(strict_types=1);

namespace Preset\Application\CommandHandlers;

use Preset\Application\Commands\ListPresetsCommand;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Shared\Domain\ValueObjects\CursorDirection;
use Traversable;

class ListPresetsCommandHandler
{
    public function __construct(
        private PresetRepositoryInterface $repo
    ) {}

    /**
     * @throws PresetNotFoundException
     */
    public function handle(ListPresetsCommand $cmd): Traversable
    {
        $cursor = $cmd->cursor
            ? $this->repo->ofId($cmd->cursor)
            : null;

        $presets = $this->repo
            ->sort($cmd->sortDirection, $cmd->sortParameter);

        if ($cmd->status) {
            $presets = $presets->filterByStatus($cmd->status);
        }

        if ($cmd->type) {
            $presets = $presets->filterByType($cmd->type);
        }

        if (is_bool($cmd->isLocked)) {
            $presets = $presets->filterByLock($cmd->isLocked);
        }

        if ($cmd->category) {
            $presets = $presets->filterByCategory($cmd->category);
        }

        if ($cmd->ids) {
            $presets = $presets->filterById($cmd->ids);
        }

        if ($cmd->query) {
            $presets = $presets->search($cmd->query);
        }

        if ($cmd->maxResults) {
            $presets = $presets->setMaxResults($cmd->maxResults);
        }

        if ($cursor) {
            if ($cmd->cursorDirection == CursorDirection::ENDING_BEFORE) {
                return $presets = $presets->endingBefore($cursor);
            }

            return $presets->startingAfter($cursor);
        }

        return $presets;
    }
}
