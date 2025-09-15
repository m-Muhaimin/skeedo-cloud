<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Billing\Domain\ValueObjects\CreditCount;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Workspace\Application\CommandHandlers\UpdateWorkspaceCommandHandler;
use Workspace\Domain\ValueObjects\Address;
use Workspace\Domain\ValueObjects\ApiKey;
use Workspace\Domain\ValueObjects\Name;

#[Handler(UpdateWorkspaceCommandHandler::class)]
class UpdateWorkspaceCommand
{
    public Id $id;
    public ?Name $name = null;
    public ?Id $ownerId = null;
    public ?Address $address = null;
    public ?ApiKey $openaiApiKey = null;
    public ?ApiKey $anthropicApiKey = null;
    public ?CreditCount $creditCount = null;

    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }

    public function setName(string $name): void
    {
        $this->name = new Name($name);
    }

    public function setOwnerId(string $ownerId): void
    {
        $this->ownerId = new Id($ownerId);
    }

    public function setAddress(array $address): void
    {
        $this->address = new Address($address);
    }

    public function setOpenaiApiKey(?string $key): void
    {
        $key = is_string($key) ? trim($key) : null;
        $this->openaiApiKey = new ApiKey($key ?: null);
    }

    public function setAnthropicApiKey(?string $key): void
    {
        $key = is_string($key) ? trim($key) : null;
        $this->anthropicApiKey = new ApiKey($key ?: null);
    }

    public function setCreditCount(null|int|float $count): void
    {
        $this->creditCount = new CreditCount($count);
    }
}
