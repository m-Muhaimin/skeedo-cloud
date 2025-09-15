<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency\RateProviders\CurrencyApi;

use Billing\Infrastructure\Currency\RateProviderInterface;
use Billing\Infrastructure\Currency\RateProviders\Client;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Infrastructure\Atributes\BuiltInAspect;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[BuiltInAspect]
class CurrencyApi implements RateProviderInterface
{
    public const LOOKUP_KEY = 'currencyapi';
    private string $baseUrl = 'https://api.currencyapi.com/';

    public function __construct(
        private Dispatcher $dispatcher,
        private Client $client,

        #[Inject('option.currencyapi.api_key')]
        private ?string $apiKey = null,

        #[Inject('option.currencyapi.updated_at')]
        private ?string $updatedAt = null,

        #[Inject('option.currencyapi.rates')]
        private ?array $rates = null,
    ) {}

    public function getName(): string
    {
        return 'CurrencyAPI';
    }

    public function getRate(CurrencyCode $from, CurrencyCode $to): int|float
    {
        $rates = $this->getRates();
        return $rates[$to->value] / $rates[$from->value];
    }

    private function getRates(): array
    {
        $time = time();

        if (
            $this->rates
            && $this->updatedAt
            && $this->updatedAt + 3600 * 4 >= $time
        ) {
            return $this->rates;
        }

        $rates = $this->fetchRates();

        // Save new data date
        $cmd = new SaveOptionCommand(
            'currencyapi',
            json_encode(
                [
                    'updated_at' => $time,
                    'rates' => $rates,
                ]
            )
        );

        $this->dispatcher->dispatch($cmd);
        $this->updatedAt = (string) $time;

        return $rates;
    }

    private function fetchRates(): array
    {
        $response = $this->sendRequest('GET', '/v3/latest', params: [
            'base_currency' => 'USD'
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Failed to fetch currency rates');
        }

        $body = json_decode($response->getBody()->getContents());

        $rates = [];
        foreach ($body->data as $rate) {
            $rates[$rate->code] = $rate->value;
        }

        return $rates;
    }

    private function sendRequest(
        string $method,
        string $path,
        array $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $url = $this->baseUrl . ltrim($path, '/');
        $headers['apikey'] = $this->apiKey;

        return $this->client->sendRequest(
            $method,
            $url,
            $body,
            $params,
            $headers
        );
    }
}
