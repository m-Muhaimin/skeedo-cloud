<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Anthropic;

use Ai\Domain\Exceptions\ApiException;
use Easy\Container\Attributes\Inject;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shared\Infrastructure\Config\WorkspaceKey;

use function \safe_json_encode;

class Client
{
    private string $baseUrl = 'https://api.anthropic.com/';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('option.anthropic.api_key')]
        private ?string $apiKey = null,

        #[Inject(WorkspaceKey::Anthropic)]
        private ?string $customApiKey = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(
        string $method,
        string $path,
        array $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $baseUrl = $this->baseUrl;

        $req = $this->requestFactory
            ->createRequest($method, $baseUrl . $path)
            ->withHeader('X-Api-Key', ($this->customApiKey ?: $this->apiKey))
            ->withHeader('Anthropic-Version', self::ANTHROPIC_VERSION)
            ->withHeader('Content-Type', 'application/json');

        if ($params) {
            $req = $req->withUri(
                $req->getUri()->withQuery(http_build_query($params))
            );
        }

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        $isMultiPart = false;
        $contentType = $req->getHeaderLine('Content-Type');
        if (str_starts_with($contentType, 'multipart/')) {
            $isMultiPart = true;
        }

        if ($isMultiPart) {
            $builder = new MultipartStreamBuilder($this->streamFactory);

            foreach ($body as $key => $value) {
                $builder->addResource($key, $value);
            }

            $multipartStream = $builder->build();
            $boundary = $builder->getBoundary();

            $req = $req
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
                ->withBody($multipartStream);
        } else if ($body) {
            $req = $req
                ->withBody($this->streamFactory->createStream(
                    safe_json_encode($body, JSON_THROW_ON_ERROR)
                ));
        }

        $resp = $this->client->sendRequest($req);

        $code = $resp->getStatusCode();
        if ($code === 401) {
            throw new ApiException('Incorrect Anthropic API key provided. Please contact your workspace owner.');
        } elseif ($code !== 200) {
            $contents = $resp->getBody()->getContents();
            $body = json_decode($contents);

            $msg = $body ? ($body->error->message ?? $body->error->type ?? 'Unexpected error occurred while communicating with Anthropic API') : $contents;
            throw new ApiException($msg);
        }

        return $resp;
    }

    public function hasCustomKey(): bool
    {
        return $this->customApiKey !== null;
    }
}
