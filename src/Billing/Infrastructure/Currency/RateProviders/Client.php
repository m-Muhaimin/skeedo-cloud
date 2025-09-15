<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency\RateProviders;

use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(
        string $method,
        string $url,
        array|string $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $req = $this->requestFactory
            ->createRequest($method, $url);

        if ($body) {
            $req = $req
                ->withBody($this->streamFactory->createStream(
                    is_array($body) ? json_encode($body) : $body
                ));
        }

        if ($params) {
            $req = $req->withUri(
                $req->getUri()->withQuery(http_build_query($params))
            );
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $req = $req->withHeader($key, $value);
            }
        }

        return $this->client->sendRequest($req);
    }
}
