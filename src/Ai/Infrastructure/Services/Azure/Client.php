<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Azure;

use Easy\Container\Attributes\Inject;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private string $base = 'tts.speech.microsoft.com';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('option.azure.speech.region')]
        private ?string $region = null,

        #[Inject('option.azure.speech.api_key')]
        private ?string $apiKey = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(
        string $method,
        string $path,
        string|array $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        if (!$this->apiKey) {
            throw new ClientException('Azure API key is not set');
        }

        $baseUrl = 'https://' . ($this->region ? $this->region . '.' : '') .  $this->base;

        $req = $this->requestFactory
            ->createRequest($method, $baseUrl . $path)
            ->withHeader('Ocp-Apim-Subscription-Key', $this->apiKey);

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
