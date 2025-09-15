<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Affiliate\Domain\Entities\PayoutEntity;
use Application;
use Billing\Infrastructure\Currency\ExchangeInterface;
use JsonSerializable;
use Override;
use Presentation\Resources\CurrencyResource;
use Presentation\Resources\DateTimeResource;

class PayoutResource implements JsonSerializable
{
    public function __construct(
        private PayoutEntity $payout,
        private array $extend = []
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $p = $this->payout;

        $exchange = Application::make(ExchangeInterface::class);
        $to = Application::make('option.billing.currency', 'USD');

        $data = [
            'id' => $p->getId(),
            'status' => $p->getStatus(),
            'amount' => $exchange->convert($p->getAmount(), 'USD', $to),
            'created_at' => new DateTimeResource($p->getCreatedAt()),
            'updated_at' => new DateTimeResource($p->getUpdatedAt()),
            'currency' => new CurrencyResource($to),
            'affiliate' => $p->getAffiliate()->getId(),
        ];

        if (in_array('affiliate', $this->extend)) {
            $extend = array_filter($this->extend, fn($item) => str_starts_with($item, 'affiliate.'));
            $extend = array_map(fn($item) => substr($item, 10), $extend);

            $data['affiliate'] = new AffiliateResource($p->getAffiliate(), $extend);
        }

        return $data;
    }
}
