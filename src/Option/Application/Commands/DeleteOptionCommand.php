<?php

declare(strict_types=1);

namespace Option\Application\Commands;

use Option\Application\CommandHandlers\DeleteOptionCommandHandler;
use Option\Domain\ValueObjects\Key;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeleteOptionCommandHandler::class)]
class DeleteOptionCommand
{
    public ?Id $id = null;
    public ?Key $key = null;

    public function __construct(string $idOrKey)
    {
        try {
            $this->id = new Id($idOrKey);
        } catch (\Throwable $th) {
            $this->key = new Key($idOrKey);
        }
    }
}
