<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Affiliate\Domain\Entities\AffiliateEntity;
use Application;
use Billing\Infrastructure\Currency\ExchangeInterface;
use JsonSerializable;
use Presentation\Resources\CurrencyResource;

class AffiliateResource implements JsonSerializable
{
    public function __construct(
        private AffiliateEntity $aff,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $u = $this->aff;
        $exchange = Application::make(ExchangeInterface::class);
        $to = Application::make('option.billing.currency', 'USD');

        $data = [
            'id' => $u->getId(),
            'paypal_email' => $u->getPayPalEmail(),
            'bank_requisites' => $u->getBankRequisites(),
            'code' => $u->getCode(),
            'clicks' => $u->getClickCount(),
            'referrals' => $u->getReferralCount(),
            'balance' => $exchange->convert($u->getBalance(), 'USD', $to),
            'pending' => $exchange->convert($u->getPending(), 'USD', $to),
            'withdrawn' => $exchange->convert($u->getWithdrawn(), 'USD', $to),
            'currency' => new CurrencyResource($to),
            'payout_method' => $u->getPayoutMethod(),
            'user' => $u->getUser()->getId(),
        ];

        if (in_array('user', $this->extend)) {
            $data['user'] = new UserResource($u->getUser());
        }

        return $data;
    }
}
