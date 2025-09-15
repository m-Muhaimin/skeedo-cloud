<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\TrackAffiliateCommandHandler;
use Affiliate\Domain\ValueObjects\Amount;
use Affiliate\Domain\ValueObjects\Code;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(TrackAffiliateCommandHandler::class)]
class TrackAffiliateCommand
{
    public Code $code;
    public string $action;
    public ?Amount $amount = null;

    public function __construct(Code|string $code, string $action)
    {
        $this->code = $code instanceof Code ? $code : new Code($code);
        $this->action = $action;
    }

    public function setAmount(Amount|int $amount): void
    {
        $this->amount = $amount instanceof Amount ? $amount : new Amount($amount);
    }
}
