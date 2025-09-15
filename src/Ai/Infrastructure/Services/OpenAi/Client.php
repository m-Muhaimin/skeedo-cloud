<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Exceptions\ApiException;
use Easy\Container\Attributes\Inject;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Shared\Infrastructure\Config\WorkspaceKey;

use function \safe_json_encode;

class Client
{
    private string $baseUrl = 'https://api.openai.com/';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('option.openai.api_secret_key')]
        private ?string $apiKey = null,

        #[Inject(WorkspaceKey::OpenAI)]
        private ?string $customApiKey = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws ApiException
     * @throws RuntimeException
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
            ->withHeader('Authorization', 'Bearer ' . ($this->customApiKey ?: $this->apiKey))
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
            throw new ApiException('Incorrect OpenAI API key provided. Please contact your workspace owner.');
        } elseif ($code === 429) {
            $requestsLeft = $resp->getHeaderLine('x-ratelimit-remaining-requests') ?? 0;
            $tokensLeft = $resp->getHeaderLine('x-ratelimit-remaining-tokens') ?? 0;

            $wait = $tokensLeft == 0 ? $resp->getHeaderLine('x-ratelimit-reset-tokens') : $resp->getHeaderLine('x-ratelimit-reset-requests');
            if (!$wait) {
                $wait = '10s';
            }
            // Parse wait time like "2h6m34s", "6m0s", or "1s" into seconds
            if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(\d+)s$/', $wait, $matches)) {
                $hours = isset($matches[1]) ? (int)$matches[1] : 0;
                $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
                $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
                $wait = $hours * 3600 + $minutes * 60 + $seconds;
            } else {
                $wait = (int) $wait; // fallback if format is unexpected
            }

            if ($wait > 60) {
                throw new ApiException('OpenAI API rate limit exceeded. Please try again later.');
            }

            sleep($wait);
            return $this->sendRequest($method, $path, $body, $params, $headers);
        } elseif ($code !== 200) {
            $contents = $resp->getBody()->getContents();
            $body = json_decode($contents);

            $msg = $body ? ($body->error->message ?? $body->error->code ?? 'Unexpected error occurred while communicating with OpenAI API') : $contents;
            throw new ApiException($msg);
        }

        return $resp;
    }

    public function hasCustomKey(): bool
    {
        return $this->customApiKey !== null;
    }
}
