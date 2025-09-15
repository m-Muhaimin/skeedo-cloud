<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Affiliate\Domain\Entities\PayoutEntity;
use Application;
use Billing\Infrastructure\Currency\ExchangeInterface;
use JsonSerializable;
use Override;
use Presentation\Resources\CurrencyResource;
use Presentation\Resources\DateTimeResource;

class PayoutResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private PayoutEntity $payout
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $p = $this->payout;

        $exchange = Application::make(ExchangeInterface::class);
        $to = Application::make('option.billing.currency', 'USD');

        return [
            'id' => $p->getId(),
            'status' => $p->getStatus(),
            'amount' => $exchange->convert($p->getAmount(), 'USD', $to),
            'created_at' => new DateTimeResource($p->getCreatedAt()),
            'updated_at' => new DateTimeResource($p->getUpdatedAt()),
            'currency' => new CurrencyResource($to),
        ];
    }
}
