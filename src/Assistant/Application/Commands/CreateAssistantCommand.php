<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Ai\Domain\ValueObjects\Instructions;
use Ai\Domain\ValueObjects\Model;
use Assistant\Application\CommandHandlers\CreateAssistantCommandHandler;
use Assistant\Domain\ValueObjects\AvatarUrl;
use Assistant\Domain\ValueObjects\Description;
use Assistant\Domain\ValueObjects\Expertise;
use Assistant\Domain\ValueObjects\Name;
use Assistant\Domain\ValueObjects\Status;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreateAssistantCommandHandler::class)]
class CreateAssistantCommand
{
    public Name $name;
    public ?Expertise $expertise = null;
    public ?Description $description = null;
    public ?Instructions $instructions = null;
    public ?AvatarUrl $avatar = null;
    public ?Status $status = null;
    public ?Model $model = null;

    public function __construct(string|Name $name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);
    }

    public function setExpertise(null|string|Expertise $expertise): void
    {
        $this->expertise = $expertise instanceof Expertise
            ? $expertise : new Expertise($expertise);
    }

    public function setDescription(null|string|Description $description): void
    {
        $this->description = $description instanceof Description
            ? $description : new Description($description);
    }

    public function setInstructions(null|string|Instructions $instructions): void
    {
        $this->instructions = $instructions instanceof Instructions
            ? $instructions : new Instructions($instructions);
    }

    public function setAvatar(null|string|AvatarUrl $avatar): void
    {
        $this->avatar = $avatar instanceof AvatarUrl
            ? $avatar : new AvatarUrl($avatar);
    }

    public function setStatus(int|Status $status): void
    {
        $this->status = $status instanceof Status
            ? $status : Status::from($status);
    }

    public function setModel(null|string|Model $model): void
    {
        $this->model = $model instanceof Model ? $model : new Model($model);
    }
}
