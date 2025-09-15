<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Ollama;

use Ai\Domain\Exceptions\ApiException;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;

use function \safe_json_encode;

class Client
{
    private ?string $baseUrl;

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private ModelRegistry $registry,
    ) {
        $this->baseUrl = $this->registry['directory']['ollama']['server'] ?? null;

        if ($this->baseUrl === null) {
            $this->baseUrl = 'http://localhost:11434';
        }

        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

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
            ->createRequest($method, ($baseUrl ?: '') . $path)
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
        if ($code !== 200) {
            $contents = $resp->getBody()->getContents();
            $body = json_decode($contents);

            $msg = $body ? ($body->error ?? 'Unexpected error occurred while communicating with Ollama API') : $contents;
            throw new ApiException($msg);
        }

        return $resp;
    }
}
