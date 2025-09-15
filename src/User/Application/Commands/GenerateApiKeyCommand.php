<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\GenerateApiKeyCommandHandler;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Email;

#[Handler(GenerateApiKeyCommandHandler::class)]
class GenerateApiKeyCommand
{
    public Id|Email|UserEntity $id;

    public function __construct(string|Id|Email|UserEntity $id)
    {
        $this->id = is_string($id) ? new Id($id) : $id;
    }
}
