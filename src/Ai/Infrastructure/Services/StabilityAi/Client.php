<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\StabilityAi;

use Ai\Domain\Exceptions\ApiException;
use Easy\Container\Attributes\Inject;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private const BASE_URL = "https://api.stability.ai/";

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('version')]
        private string $version = '1.0.0',

        #[Inject('option.stabilityai.api_key')]
        private ?string $apiKey = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(
        string $method,
        string $path,
        array $data = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $path = parse_url($path, PHP_URL_SCHEME) !== null
            ? $path
            : self::BASE_URL . ltrim($path, '/');

        $req = $this->requestFactory
            ->createRequest($method, $path);

        $req = $req->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Stability-Client-ID', 'aikeedo')
            ->withHeader('Stability-Client-Version', trim($this->version));

        if ($this->apiKey) {
            $req = $req->withHeader('Authorization', 'Bearer ' . $this->apiKey);
        }

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

            foreach ($data as $key => $value) {
                $builder->addResource($key, $value);
            }

            $multipartStream = $builder->build();
            $boundary = $builder->getBoundary();

            $req = $req
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
                ->withBody($multipartStream);
        } else {
            if ($data) {
                $stream = $req->getBody();
                $stream->write(json_encode($data));
                $req = $req->withBody($stream);
            }
        }

        $resp = $this->client->sendRequest($req);
        $code = $resp->getStatusCode();

        if ($code === 401) {
            throw new ApiException('Incorrect Stability AI API key provided. Please contact your workspace owner.');
        } elseif ($code < 200 || $code >= 300) {
            $contents = $resp->getBody()->getContents();
            $body = json_decode($contents);

            $msg = $body ? ($body->errors[0] ?? 'Unexpected error occurred while communicating with Stability AI API') : $contents;
            throw new ApiException($msg);
        }

        return $resp;
    }
}
