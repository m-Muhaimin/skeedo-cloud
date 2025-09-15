<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\CountLibraryItemsCommandHandler;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\ValueObjects\ItemType;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\State;
use DateTime;
use DateTimeInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

/**
 * @template T of AbstractLibraryItemEntity
 */
#[Handler(CountLibraryItemsCommandHandler::class)]
class CountLibraryItemsCommand
{
    public null|Id|UserEntity $user = null;
    public null|Id|WorkspaceEntity $workspace = null;
    public ?ItemType $type = null;
    public ?Model $model = null;
    public ?array $state = null;
    public ?DateTimeInterface $createdAfter = null;
    public ?DateTimeInterface $createdBefore = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setModel(string $model): void
    {
        $this->model = new Model($model);
    }

    public function setState(State ...$states): void
    {
        $this->state = $states;
    }

    public function createdAfter(string $after): self
    {
        $date = new DateTime($after);

        // If the original string doesn't contain time information, set it to 00:00:00
        if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?/', $after)) {
            $date->setTime(0, 0, 0);
        }

        $this->createdAfter = $date;

        return $this;
    }

    public function createdBefore(string $before): self
    {
        $date = new DateTime($before);

        // If the original string doesn't contain time information, set it to 23:59:59
        if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?/', $before)) {
            $date->setTime(23, 59, 59);
        }

        $this->createdBefore = $date;

        return $this;
    }
}
