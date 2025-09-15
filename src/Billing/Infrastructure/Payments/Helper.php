<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\ValueObjects\Price;
use Billing\Infrastructure\Currency\ExchangeInterface;
use Easy\Container\Attributes\Inject;
use Shared\Domain\ValueObjects\CurrencyCode;
use Throwable;

class Helper
{
    public function __construct(
        private ExchangeInterface $exchange,

        #[Inject('option.site.domain')]
        private ?string $domain = null,

        #[Inject('option.site.is_secure')]
        private ?string $isSecure = null,
    ) {}

    public function generateReturnUrl(OrderEntity $order, string $gateway): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/payment-callback/%s/%s',
            $protocol,
            $domain,
            $order->getId()->getValue(),
            $gateway,
        );
    }

    public function generateCancelUrl(OrderEntity $order, string $gateway): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/app/billing/',
            $protocol,
            $domain
        );
    }

    public function generateWebhookUrl(OrderEntity $order, string $gateway): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/webhooks/%s',
            $protocol,
            $domain,
            $gateway,
        );
    }

    /**
     * Generate a newprice in the gateway's selected currency. 
     * 
     * @param Price $amount Amount to be converted
     * @param CurrencyCode $base Current currency
     * @param CurrencyCode|null|string $target Target currency
     * @return array{0:Price,1:CurrencyCode} Converted amount and currency.
     */
    public function convert(
        Price $amount,
        CurrencyCode $base,
        null|string|CurrencyCode $target = null
    ): array {
        if (!$target) {
            return [$amount, $base];
        }

        try {
            if (is_string($target)) {
                $target = CurrencyCode::from($target);
            }

            $amount = $this->exchange->convert(
                $amount,
                $base,
                $target
            );

            $base = $target;
        } catch (Throwable $th) {
            // Could not convert currency
        }

        return [$amount, $base];
    }
}
