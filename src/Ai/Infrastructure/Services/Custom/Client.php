<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Custom;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Model;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;

use function \safe_json_encode;

class Client
{
    private array $llms = [];

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private UriFactoryInterface $uriFactory,
        private StreamFactoryInterface $streamFactory,
        private ModelRegistry $registry,
    ) {
        $this->llms = array_filter($this->registry['directory'], function ($llm) {
            return $llm['custom'] ?? false;
        });

        $this->llms = array_values($this->llms);
    }

    /**
     * @return ResponseInterface
     * @throws ModelNotSupportedException
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws ApiException
     * @throws RuntimeException
     */
    public function sendRequest(
        Model $model,
        string $method,
        string $path,
        array $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $baseUri = $this->getBaseUri($model);

        $uri = $this->uriFactory->createUri($baseUri);
        $uri = $uri->withPath(
            $uri->getPath() . "/" . trim($path, '/')
        );

        $req = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Content-Type', 'application/json');

        $req = $this->applyHeaders($model, $req);

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
            throw new ApiException('Incorrect API key provided. Please contact your workspace owner.');
        } elseif ($code !== 200) {
            $contents = $resp->getBody()->getContents();
            $body = json_decode($contents);

            $msg = $body ? ($body->error->message ?? $body->error->code ?? $body->error ?? 'Unexpected error occurred while communicating with API. Code: ' . $code) : $contents;
            throw new ApiException($msg);
        }

        return $resp;
    }

    /**
     * Get the base URI for the given model
     *
     * @param Model $model The model to get the base URI for
     * @return string The base URI for the given model
     * @throws ModelNotSupportedException If the model is not supported
     */
    private function getBaseUri(Model $model): string
    {
        $modelName = str_contains($model->value, '/')
            ? explode('/', $model->value, 2)[1]
            : $model->value;

        foreach ($this->llms as $llm) {
            if (in_array($model->value, array_column($llm['models'], 'key'))) {
                $url = rtrim($llm['server'], '/');

                // Replace {model} with the actual model name
                $url = preg_replace('/\{\s*model\s*\}/', $modelName, $url);
                return $url;
            }
        }

        throw new ModelNotSupportedException($model);
    }

    /**
     * Apply the headers to the request
     *
     * @param Model $model The model to apply the headers to
     * @param RequestInterface $req The request to apply the headers to
     * @return RequestInterface The request with the headers applied
     * @throws InvalidArgumentException
     */
    private function applyHeaders(Model $model, RequestInterface $req): RequestInterface
    {
        foreach ($this->llms as $llm) {
            if (!in_array($model->value, array_column($llm['models'], 'key'))) {
                continue;
            }

            if (isset($llm['api_key'])) {
                $req = $req->withHeader('Authorization', 'Bearer ' . $llm['api_key']);
            }

            foreach ($llm['headers'] ?? [] as $group) {
                $req = $req->withHeader($group['key'], $group['value']);
            }
        }

        return $req;
    }
}
